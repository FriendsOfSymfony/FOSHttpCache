<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\Exception\InvalidUrlException;
use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\RequestException;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\UriFactory;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Queue and send HTTP requests with a Httplug asynchronous client.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class HttpDispatcher implements Dispatcher
{
    /**
     * @var HttpAsyncClient
     */
    private $httpClient;

    /**
     * @var UriFactory
     */
    private $uriFactory;

    /**
     * Queued requests.
     *
     * @var RequestInterface[]
     */
    private $queue = [];

    /**
     * Caching proxy server host names or IP addresses.
     *
     * @var UriInterface[]
     */
    private $servers;

    /**
     * Application host name and optional base URL.
     *
     * @var UriInterface[]
     */
    private $baseUris;

    /**
     * If you specify a custom HTTP client, make sure that it converts HTTP
     * errors to exceptions.
     *
     * If your proxy server IPs can not be statically configured, extend this
     * class and overwrite getServers. Be sure to have some caching in
     * getServers.
     *
     * @param string[]             $servers    Caching proxy server hostnames or IP
     *                                         addresses, including port if not port 80.
     *                                         E.g. ['127.0.0.1:6081']
     * @param string|string[]      $baseUris   Default application hostnames, optionally
     *                                         including base URL, for purge and refresh
     *                                         requests (optional). At least one is required if
     *                                         you purge and refresh paths instead of
     *                                         absolute URLs. A request will be sent for each
     *                                         base URL.     *
     * @param HttpAsyncClient|null $httpClient Client capable of sending HTTP requests. If no
     *                                         client is supplied, a default one is created
     * @param UriFactory|null      $uriFactory Factory for PSR-7 URIs. If not specified, a
     *                                         default one is created
     */
    public function __construct(
        array $servers,
        $baseUris = [],
        HttpAsyncClient $httpClient = null,
        UriFactory $uriFactory = null
    ) {
        if (!$httpClient) {
            $httpClient = new PluginClient(
                HttpAsyncClientDiscovery::find(),
                [new ErrorPlugin()]
            );
        }
        $this->httpClient = $httpClient;
        $this->uriFactory = $uriFactory ?: UriFactoryDiscovery::find();

        $this->setServers($servers);

        // Support both, a string or an array of strings (array_filter to kill empty base URLs)
        $baseUris = array_filter((array) $baseUris);

        $this->setBaseUris($baseUris);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(RequestInterface $invalidationRequest, $validateHost = true)
    {
        if ($validateHost && 0 === \count($this->baseUris) && !$invalidationRequest->getUri()->getHost()) {
            throw MissingHostException::missingHost((string) $invalidationRequest->getUri());
        }

        $signature = $this->getRequestSignature($invalidationRequest);

        if (isset($this->queue[$signature])) {
            return;
        }

        $this->queue[$signature] = $invalidationRequest;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $queue = $this->queue;
        $this->queue = [];
        /** @var Promise[] $promises */
        $promises = [];

        $exceptions = new ExceptionCollection();

        foreach ($queue as $request) {
            foreach ($this->fanOut($request) as $proxyRequest) {
                try {
                    $promises[] = $this->httpClient->sendAsyncRequest($proxyRequest);
                } catch (\Exception $e) {
                    $exceptions->add(new InvalidArgumentException($e));
                }
            }
        }

        foreach ($promises as $promise) {
            try {
                $promise->wait();
            } catch (HttpException $exception) {
                $exceptions->add(ProxyResponseException::proxyResponse($exception));
            } catch (RequestException $exception) {
                $exceptions->add(ProxyUnreachableException::proxyUnreachable($exception));
            } catch (\Exception $exception) {
                // @codeCoverageIgnoreStart
                $exceptions->add(new InvalidArgumentException($exception));
                // @codeCoverageIgnoreEnd
            }
        }

        if (count($exceptions)) {
            throw $exceptions;
        }

        return count($queue);
    }

    /**
     * Get the list of servers to send invalidation requests to.
     *
     * @return UriInterface[]
     */
    protected function getServers()
    {
        return $this->servers;
    }

    /**
     * Duplicate a request for each caching server.
     *
     * @param RequestInterface $request The request to duplicate for each configured server
     *
     * @return RequestInterface[]
     */
    private function fanOut(RequestInterface $request)
    {
        /** @var RequestInterface[] $requests */
        $requests = [$request];

        // If base URIs are configured, try to make partial invalidation
        // requests complete and send out a request for every base URI
        if (0 !== \count($this->baseUris)) {

            $requests = [];

            foreach ($this->baseUris as $baseUri) {
                $uri = $request->getUri();

                if ($uri->getHost()) {
                    // Absolute URI: does it already have a scheme?
                    if (!$uri->getScheme() && '' !== $baseUri->getScheme()) {
                        $uri = $uri->withScheme($baseUri->getScheme());
                    }
                } else {
                    // Relative URI
                    if ('' !== $baseUri->getHost()) {
                        $uri = $uri->withHost($baseUri->getHost());
                    }

                    if ($baseUri->getPort()) {
                        $uri = $uri->withPort($baseUri->getPort());
                    }

                    // Base path
                    if ('' !== $baseUri->getPath()) {
                        $path = $baseUri->getPath().'/'.ltrim($uri->getPath(), '/');
                        $uri = $uri->withPath($path);
                    }
                }

                // Close connections to make sure invalidation (PURGE/BAN) requests
                // will not interfere with content (GET) requests.
                $requests[] = $request->withUri($uri)->withHeader('Connection', 'Close');
            }
        }

        $serverRequests = [];

        // Create all requests to each caching proxy server
        foreach ($requests as $request) {

            $uri = $request->getUri();

            foreach ($this->getServers() as $server) {
                $serverRequests[] = $request->withUri(
                    $uri
                        ->withScheme($server->getScheme())
                        ->withHost($server->getHost())
                        ->withPort($server->getPort()),
                    true    // Preserve application Host header
                );
            }
        }

        return $serverRequests;
    }

    /**
     * Set caching proxy server URI objects, validating them.
     *
     * @param string[] $servers Caching proxy proxy server hostnames or IP
     *                          addresses, including port if not port 80.
     *                          E.g. ['127.0.0.1:6081']
     *
     * @throws InvalidUrlException If server is invalid or contains URL
     *                             parts other than scheme, host, port
     */
    private function setServers(array $servers)
    {
        $this->servers = [];
        foreach ($servers as $server) {
            $this->servers[] = $this->filterUri($server, ['scheme', 'host', 'port']);
        }
    }

    /**
     * Set application base URI that will be prefixed to relative purge and
     * refresh requests, and validate it.
     *
     * @param array $baseUris Your application’s base URIs
     *
     * @throws InvalidUrlException If the base URI is not a valid URI
     */
    private function setBaseUris(array $baseUris = [])
    {
        if (0 === \count($baseUris)) {
            $this->baseUris = [];

            return;
        }

        foreach ($baseUris as $baseUri) {
            $this->baseUris[] =  $this->filterUri($baseUri);
        }
    }

    /**
     * Filter a URL.
     *
     * Prefix the URL with "http://" if it has no scheme, then check the URL
     * for validity. You can specify what parts of the URL are allowed.
     *
     * @param string   $uriString
     * @param string[] $allowedParts Array of allowed URL parts (optional)
     *
     * @return UriInterface Filtered URI (with default scheme if there was no scheme)
     *
     * @throws InvalidUrlException If URL is invalid, the scheme is not http or
     *                             contains parts that are not expected
     */
    private function filterUri($uriString, array $allowedParts = [])
    {
        if (!is_string($uriString)) {
            throw new \InvalidArgumentException(sprintf(
                'URI parameter must be a string, %s given',
                gettype($uriString)
            ));
        }

        // Creating a PSR-7 URI without scheme (with parse_url) results in the
        // original hostname to be seen as path. So first add a scheme if none
        // is given.
        if (false === strpos($uriString, '://')) {
            $uriString = sprintf('%s://%s', 'http', $uriString);
        }

        try {
            $uri = $this->uriFactory->createUri($uriString);
        } catch (\InvalidArgumentException $e) {
            throw InvalidUrlException::invalidUrl($uriString);
        }

        if (!$uri->getScheme()) {
            throw InvalidUrlException::invalidUrl($uriString, 'empty scheme');
        }

        if (count($allowedParts) > 0) {
            $parts = parse_url((string) $uri);
            $diff = array_diff(array_keys($parts), $allowedParts);
            if (count($diff) > 0) {
                throw InvalidUrlException::invalidUrlParts($uriString, $allowedParts);
            }
        }

        return $uri;
    }

    /**
     * Build a request signature based on the request data. Unique for every different request, identical
     * for the same requests.
     *
     * This signature is used to avoid sending the same invalidation request twice.
     *
     * @param RequestInterface $request An invalidation request
     *
     * @return string A signature for this request
     */
    private function getRequestSignature(RequestInterface $request)
    {
        $headers = $request->getHeaders();
        ksort($headers);

        return sha1($request->getMethod()."\n".$request->getUri()."\n".var_export($headers, true));
    }
}
