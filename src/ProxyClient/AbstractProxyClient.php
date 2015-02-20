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
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\ProxyClient\Request\InvalidationRequest;
use FOS\HttpCache\ProxyClient\Request\RequestQueue;
use Http\Adapter\Exception\MultiHttpAdapterException;
use Http\Adapter\HttpAdapter;
use Http\Discovery\HttpAdapterDiscovery;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstract caching proxy client
 *
 * @author David de Boer <david@driebit.nl>
 */
abstract class AbstractProxyClient implements ProxyClientInterface
{
    /**
     * HTTP client
     *
     * @var HttpAdapter
     */
    private $httpAdapter;

    /**
     * Request queue
     *
     * @var RequestQueue
     */
    protected $queue;

    /**
     * Constructor
     *
     * @param array $servers           Caching proxy server hostnames or IP
     *                                 addresses, including port if not port 80.
     *                                 E.g. ['127.0.0.1:6081']
     * @param string      $baseUri     Default application hostname, optionally
     *                                 including base URL, for purge and refresh
     *                                 requests (optional). This is required if
     *                                 you purge and refresh paths instead of
     *                                 absolute URLs.
     * @param HttpAdapter $httpAdapter If no HTTP adapter is supplied, a default
     *                                 one will be created.
     */
    public function __construct(
        array $servers,
        $baseUri = null,
        HttpAdapter $httpAdapter = null
    ) {
        $this->httpAdapter = $httpAdapter ?: HttpAdapterDiscovery::find();
        $this->initQueue($servers, $baseUri);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (0 === $this->queue->count()) {
            return 0;
        }

        $queue = clone $this->queue;
        $this->queue->clear();

        try {
            $responses = $this->httpAdapter->sendRequests($queue->all());
        } catch (MultiHttpAdapterException $e) {
            // Handle all networking errors: php-http only throws an exception
            // if no response is available.
            $collection = new ExceptionCollection();
            foreach ($e->getExceptions() as $exception) {
                // php-http only throws an exception if no response is available
                if (!$exception->getResponse()) {
                    // Assume networking error if no response was returned.
                    $collection->add(
                        ProxyUnreachableException::proxyUnreachable($exception)
                    );
                }
            }

            foreach ($this->handleErrorResponses($e->getResponses()) as $exception) {
                $collection->add($exception);
            }

            throw $collection;
        }

        $exceptions = $this->handleErrorResponses($responses);
        if (count($exceptions) > 0) {
            throw new ExceptionCollection($exceptions);
        }

        return count($queue);
    }

    /**
     * Add invalidation reqest to the queue
     *
     * @param string $method  HTTP method
     * @param string $url     HTTP URL
     * @param array  $headers HTTP headers
     */
    protected function queueRequest($method, $url, array $headers = [])
    {
        $this->queue->add(new InvalidationRequest($method, $url, $headers));
    }

    /**
     * Initialize the request queue
     *
     * @param array  $servers
     * @param string $baseUri
     */
    protected function initQueue(array $servers, $baseUri)
    {
        $this->queue = new RequestQueue($servers, $baseUri);
    }

    /**
     * @param ResponseInterface[] $responses
     *
     * @return ProxyResponseException[]
     */
    private function handleErrorResponses(array $responses)
    {
        $exceptions = [];

        foreach ($responses as $response) {
            if ($response->getStatusCode() >= 400
                && $response->getStatusCode() < 600
            ) {
                $exceptions[] = ProxyResponseException::proxyResponse($response);
            }
        }

        return $exceptions;
    }
}
