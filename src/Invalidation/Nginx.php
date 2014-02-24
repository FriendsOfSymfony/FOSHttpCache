<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\Invalidation\Method\RefreshInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Nginx HTTP cache invalidator.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 *
 * AFAIK Nginx doesn't have BAN. We just implement Purge and Refresh
 *
 */
class Nginx implements RefreshInterface
{
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_HOST         = 'X-Host';
    const HTTP_HEADER_URL          = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';
    const HTTP_HEADER_CACHE        = 'X-Cache-Tags';

    /**
     * IP addresses of all Nginx instances
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
     * @param array           $ips    Nginx IP addresses including port if
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
     *
     * @throws \UnexpectedValueException
     * @throws MissingHostException
     */
    protected function queueRequest($method, $url, array $headers = array())
    {
        $request = $this->client->createRequest($method, $url, $headers);

        $this->queue[] = $request;

        return $request;
    }

    /**
     * Sends all requests to each Nginx instance
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
                $nginxRequest = $this->client->createRequest(
                    $request->getMethod(),
                    $ip . $request->getResource(),
                    $request->getHeaders()
                );
                $allRequests[] = $nginxRequest;
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
            // Usually 'couldn't connect to host', which means: Nginx is down
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
