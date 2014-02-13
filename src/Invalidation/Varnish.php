<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\HostUnreachableException;
use FOS\HttpCache\Exception\InvalidUrlException;
use FOS\HttpCache\Exception\InvalidUrlPartsException;
use FOS\HttpCache\Exception\InvalidUrlSchemeException;
use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\Exception\ResponseException;
use FOS\HttpCache\Invalidation\Method\BanInterface;
use FOS\HttpCache\Invalidation\Method\PurgeInterface;
use FOS\HttpCache\Invalidation\Method\RefreshInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Message\RequestInterface;

/**
 * Varnish HTTP cache invalidator.
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish implements BanInterface, PurgeInterface, RefreshInterface
{
    const HTTP_METHOD_BAN          = 'BAN';
    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_HOST         = 'X-Host';
    const HTTP_HEADER_URL          = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';
    const HTTP_HEADER_CACHE        = 'X-Cache-Tags';

    /**
     * IP addresses/hostnames of all Varnish instances
     *
     * @var array
     */
    protected $servers;

    /**
     * Map of default headers for ban requests with their default values.
     *
     * @var array
     */
    protected $defaultBanHeaders = array(
        self::HTTP_HEADER_HOST         => self::REGEX_MATCH_ALL,
        self::HTTP_HEADER_URL          => self::REGEX_MATCH_ALL,
        self::HTTP_HEADER_CONTENT_TYPE => self::REGEX_MATCH_ALL
    );

    /**
     * HTTP client
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * Request queue
     *
     * @var array|RequestInterface[]
     */
    protected $queue;

    /**
     * Constructor
     *
     * @param array           $servers Varnish server hostnames or IP addresses,
     *                                 including port if not port 80.
     *                                 E.g. array('127.0.0.1:6081')
     * @param string          $baseUrl Default application hostname, optionally
     *                                 including base URL, for purge and refresh
     *                                 requests (optional). This is required if
     *                                 you purge and refresh paths instead of
     *                                 absolute URLs.
     * @param ClientInterface $client  HTTP client (optional). If no HTTP client
     *                                 is supplied, a default one will be
     *                                 created.
     */
    public function __construct(array $servers, $baseUrl = null, ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
        $this->setServers($servers);
        $this->setBaseUrl($baseUrl);
    }

    /**
     * Set Varnish servers
     *
     * @param array $servers Varnish server hostnames or IP addresses,
     *                       including port if not port 80.
     *                       E.g. array('127.0.0.1:6081')
     *
     * @throws InvalidUrlSchemeException If scheme is supplied and is not HTTP
     * @throws InvalidUrlException       If server is invalid or contains URL
     *                                   parts other than scheme, host, port
     */
    public function setServers(array $servers)
    {
        $this->servers = array();
        foreach ($servers as $server) {
            $this->servers[] = $this->filterUrl($server, array('scheme', 'host', 'port'));
        }
    }

    /**
     * Set application hostname, optionally including a base URL, for purge and
     * refresh requests
     *
     * @param string $url Your application’s base URL or hostname
     */
    public function setBaseUrl($url)
    {
        if ($url) {
            $url = $this->filterUrl($url);
        }

        $this->client->setBaseUrl($url);
    }

    /**
     * Set the default headers that get merged with the provided headers in self::ban().
     *
     * @param array $headers Hashmap with keys being the header names, values
     *                       the header values.
     */
    public function setDefaultBanHeaders(array $headers)
    {
        $this->defaultBanHeaders = $headers;
    }

    /**
     * Add or overwrite a default ban header.
     *
     * @param string $name  The name of that header
     * @param string $value The content of that header
     */
    public function setDefaultBanHeader($name, $value)
    {
        $this->defaultBanHeaders[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function ban(array $headers)
    {
        $headers = array_merge(
            $this->defaultBanHeaders,
            $headers
        );

        $this->queueRequest(self::HTTP_METHOD_BAN, '/', $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function banPath($path, $contentType = null, $hosts = null)
    {
        if (is_array($hosts)) {
            if (!count($hosts)) {
                throw new \InvalidArgumentException('Either supply a list of hosts or null, but not an empty array.');
            }
            $hosts = '^('.join('|', $hosts).')$';
        }

        $headers = array(
            self::HTTP_HEADER_URL => $path,
        );

        if ($contentType) {
            $headers[self::HTTP_HEADER_CONTENT_TYPE] = $contentType;
        }
        if ($hosts) {
            $headers[self::HTTP_HEADER_HOST] = $hosts;
        }

        return $this->ban($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url)
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = array())
    {
        $headers = array_merge($headers, array('Cache-Control' => 'no-cache'));
        $this->queueRequest(self::HTTP_METHOD_REFRESH, $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (0 === count($this->queue)) {
            return;
        }

        $this->sendRequests($this->queue);
        $this->queue = array();
    }

    /**
     * Add a request to the queue
     *
     * @param string $method  HTTP method
     * @param string $url     URL
     * @param array  $headers HTTP headers
     *
     * @return RequestInterface Request that was added to the queue
     *
     * @throws \InvalidArgumentException
     * @throws MissingHostException
     */
    protected function queueRequest($method, $url, array $headers = array())
    {
        $request = $this->client->createRequest($method, $url, $headers);

        // For purge and refresh, add a host header to the request if it hasn't
        // been set
        if (self::HTTP_METHOD_BAN !== $method
            && '' == $request->getHeader('Host')
        ) {
            throw new MissingHostException($url);
        }

        $this->queue[] = $request;

        return $request;
    }

    /**
     * Sends all requests to each Varnish instance
     *
     * Requests are sent in parallel to minimise impact on performance.
     *
     * @param RequestInterface[] $requests Requests
     *
     * @throws ExceptionCollection
     */
    protected function sendRequests(array $requests)
    {
        $allRequests = array();

        foreach ($requests as $request) {
            foreach ($this->servers as $server) {
                $varnishRequest = $this->client->createRequest(
                    $request->getMethod(),
                    $server . $request->getResource(),
                    $request->getHeaders()
                );
                $allRequests[] = $varnishRequest;
            }
        }

        try {
            $this->client->send($allRequests);
        } catch (MultiTransferException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle request exception
     *
     * @param MultiTransferException $exceptions
     *
     * @throws ExceptionCollection
     */
    protected function handleException(MultiTransferException $exceptions)
    {
        $collection = new ExceptionCollection();

        foreach ($exceptions as $exception) {
            if ($exception instanceof CurlException) {
                // Varnish unreachable
                $e = new HostUnreachableException(
                    $exception->getRequest()->getHost(),
                    $exception->getMessage(),
                    $exception
                );
            } else {
                // Other error
                $e = new ResponseException(
                    $exception->getCode(),
                    $exception->getMessage(),
                    $exception
                );
            }

            $collection->add($e);
        }

        throw $collection;
    }

    /**
     * Filter a URL
     *
     * @param string $url
     * @param array  $allowedParts Array of allowed URL parts (optional)
     *
     * @throws InvalidUrlSchemeException If scheme is not HTTP
     * @throws InvalidUrlException       If URL is invalid
     * @throws InvalidUrlPartsException  If scheme contains invalid parts
     *
     * @return array
     */
    protected function filterUrl($url, array $allowedParts = array())
    {
        // parse_url doesn’t work properly when no scheme is supplied, so
        // prefix server with HTTP scheme if necessary.
        if (false === strpos($url, '://')) {
            $url = 'http://' . $url;
        }

        if (!$parts = parse_url($url)) {
            throw new InvalidUrlException($url);
        }

        if (isset($parts['scheme']) && 'http' != $parts['scheme']) {
            throw new InvalidUrlSchemeException($url, $parts['scheme'], 'http');
        }

        if (count($allowedParts) > 0) {
            $diff = array_diff(array_keys($parts), $allowedParts);
            if (count($diff) > 0) {
                throw new InvalidUrlPartsException($url, $allowedParts);
            }
        }

        return $url;
    }
}
