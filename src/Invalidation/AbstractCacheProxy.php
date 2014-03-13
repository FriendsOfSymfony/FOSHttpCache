<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\InvalidUrlException;
use FOS\HttpCache\Exception\InvalidUrlPartsException;
use FOS\HttpCache\Exception\InvalidUrlSchemeException;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\RequestInterface;

/**
 * Guzzle-based abstract caching proxy client
 *
 * @author David de Boer <david@driebit.nl>
 */
abstract class AbstractCacheProxy implements CacheProxyInterface
{
    /**
     * IP addresses/hostnames of all caching proxy servers
     *
     * @var array
     */
    protected $servers;

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
     * @param array           $servers Caching proxy server hostnames or IP addresses,
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
     * Set caching proxy servers
     *
     * @param array $servers Caching proxy proxy server hostnames or IP
     *                       addresses, including port if not port 80.
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
     * {@inheritdoc}
     */
    public function flush()
    {
        $queue = $this->queue;
        if (0 === count($queue)) {
            return 0;
        }

        $this->queue = array();
        $this->sendRequests($queue);

        return count($queue);
    }

    /**
     * Add a request to the queue
     *
     * @param string $method  HTTP method
     * @param string $url     URL
     * @param array  $headers HTTP headers
     */
    protected function queueRequest($method, $url, array $headers = array())
    {
        $this->queue[] = $this->createRequest($method, $url, $headers);
    }

    /**
     * Create request
     *
     * @param string $method  HTTP method
     * @param string $url     URL
     * @param array  $headers HTTP headers
     *
     * @return RequestInterface
     */
    protected function createRequest($method, $url, array $headers = array())
    {
        return $this->client->createRequest($method, $url, $headers);
    }

    /**
     * Sends all requests to each caching proxy server
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
                $proxyRequest = $this->client->createRequest(
                    $request->getMethod(),
                    $server . $request->getResource(),
                    $request->getHeaders()
                );
                $allRequests[] = $proxyRequest;
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
                // Caching proxy unreachable
                $e = ProxyUnreachableException::proxyUnreachable(
                    $exception->getRequest()->getHost(),
                    $exception->getMessage(),
                    $exception
                );
            } elseif ($exception instanceof RequestException) {
                // Other error
                $e = ProxyResponseException::proxyResponse(
                    $exception->getRequest()->getHost(),
                    $exception->getCode(),
                    $exception->getMessage(),
                    $exception
                );
            } else {
                // Unexpected exception type
                $e = $exception;
            }

            $collection->add($e);
        }

        throw $collection;
    }

    /**
     * Filter a URL
     *
     * Prefix the URL with "http://" if it has no scheme, then check the URL
     * for validity. You can specify what parts of the URL are allowed.
     *
     * @param string   $url
     * @param string[] $allowedParts Array of allowed URL parts (optional)
     *
     * @throws InvalidUrlException If URL is invalid, the scheme is not http or
     *                             contains parts that are not expected.
     *
     * @return string The URL (with default scheme if there was no scheme)
     */
    protected function filterUrl($url, array $allowedParts = array())
    {
        // parse_url doesn’t work properly when no scheme is supplied, so
        // prefix URL with HTTP scheme if necessary.
        if (false === strpos($url, '://')) {
            $url = sprintf('%s://%s', $this->getDefaultScheme(), $url);
        }

        if (!$parts = parse_url($url)) {
            throw InvalidUrlException::invalidUrl($url);
        }

        if (!in_array(strtolower($parts['scheme']), $this->getAllowedSchemes())) {
            throw InvalidUrlException::invalidUrlScheme($url, $parts['scheme'], $this->getAllowedSchemes());
        }

        if (count($allowedParts) > 0) {
            $diff = array_diff(array_keys($parts), $allowedParts);
            if (count($diff) > 0) {
                throw InvalidUrlException::invalidUrlParts($url, $allowedParts);
            }
        }

        return $url;
    }

    /**
     * Get default scheme
     *
     * @return string
     */
    protected function getDefaultScheme()
    {
        return 'http';
    }

    /**
     * Get schemes allowed by caching proxy
     *
     * @return string[] Array of schemes allowed by caching proxy, e.g. 'http'
     *                  or 'https'
     */
    abstract protected function getAllowedSchemes();
}
