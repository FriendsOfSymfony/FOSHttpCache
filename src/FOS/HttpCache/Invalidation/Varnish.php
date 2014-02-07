<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Invalidation\Method\BanInterface;
use FOS\HttpCache\Invalidation\Method\PurgeInterface;
use FOS\HttpCache\Invalidation\Method\RefreshInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

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
     * IP addresses of all Varnish instances
     *
     * @var array
     */
    protected $ips;

    /**
     * The hostname for purge and refresh requests.
     *
     * @var string
     */
    protected $host;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Request queue
     *
     * @var array|RequestInterface[]
     */
    protected $queue;

    /**
     * Constructor
     *
     * @param array           $ips    Varnish IP addresses including port if
     *                                not port 80. E.g. array('127.0.0.1:6081')
     * @param string          $host   Default host for purge and refresh
     *                                requests (optional). This is required if
     *                                you purge and refresh paths instead of
     *                                absolute URLs.
     * @param ClientInterface $client HTTP client (optional). If no HTTP client
     *                                is supplied, a default one will be
     *                                created.
     */
    public function __construct(array $ips, $host = null, ClientInterface $client = null)
    {
        $this->ips = $ips;
        $this->host = $host;
        $this->client = $client ?: new Client();
    }

    /**
     * Set a logger to enable logging
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
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
            self::HTTP_HEADER_URL          => $path,
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
     * Flush the queue
     *
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
     */
    protected function queueRequest($method, $url, array $headers = array())
    {
        $request = $this->client->createRequest($method, $url, $headers);

        // For purge and refresh, add a host header to the request if it hasn't
        // been set
        if (self::HTTP_METHOD_BAN !== $method) {
            if ('' == $request->getHeader('Host')) {
                $parsedUrl = parse_url($url);
                if (!isset($parsedUrl['host'])) {
                    $request->setHeader('Host', $this->host);
                }
            }
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
     */
    protected function sendRequests(array $requests)
    {
        $allRequests = array();

        foreach ($requests as $request) {
            foreach ($this->ips as $ip) {
                $varnishRequest = $this->client->createRequest(
                    $request->getMethod(),
                    $ip . $request->getResource(),
                    $request->getHeaders()
                );
                $allRequests[] = $varnishRequest;
            }
        }

        try {
            $this->client->send($allRequests);
        } catch (MultiTransferException $e) {
            foreach ($e as $ea) {
                $this->logException($ea);
            }
        }
    }

    /**
     * Log request exception
     *
     * @param RequestException $e
     */
    protected function logException(RequestException $e)
    {
        if ($e instanceof CurlException) {
            // Usually 'couldn't connect to host', which means: Varnish is down
            $level = 'crit';
        } else {
            $level = 'info';
        }

        $this->log(
            sprintf(
                'Caught exception while trying to %s %s' . PHP_EOL . 'Message: %s',
                $e->getRequest()->getMethod(),
                $e->getRequest()->getUrl(),
                $e->getMessage()
            ),
            $level
        );
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     * @param string $level   Error level (optional)
     */
    protected function log($message, $level = 'debug')
    {
        if (null !== $this->logger) {
            $this->logger->$level($message);
        }
    }
}
