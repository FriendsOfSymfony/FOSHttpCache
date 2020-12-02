<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test;

use Http\Client\HttpClient as PhpHttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Provides a very simple way to fetch HTTP responses, auto-creating a client.
 */
class HttpClient
{
    /**
     * HTTP client for requests to the application.
     *
     * @var PhpHttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $port;

    /**
     * @param string $hostname Default hostname if not specified in the URL
     * @param string $port     Default port if not specified in the URL
     */
    public function __construct($hostname, $port)
    {
        $this->hostname = $hostname;
        $this->port = $port;
    }

    /**
     * Get HTTP response from your application.
     *
     * @param string $uri     HTTP URI
     * @param array  $headers HTTP headers
     * @param string $method  HTTP method
     *
     * @return ResponseInterface
     */
    public function getResponse($uri, array $headers, $method)
    {
        $request = $this->createRequest($method, $uri, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Send PSR HTTP request to your application.
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request)
    {
        // Close connections to make sure invalidation (PURGE/BAN) requests will
        // not interfere with content (GET) requests.
        $request = $request->withHeader('Connection', 'Close');

        return $this->getHttpClient()->sendRequest($request);
    }

    /**
     * Get HTTP client for your application.
     *
     * @return PhpHttpClient
     */
    private function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = HttpClientDiscovery::find();
        }

        return $this->httpClient;
    }

    /**
     * Create a request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $headers
     *
     * @return RequestInterface
     *
     * @throws \Exception
     */
    private function createRequest($method, $uri, $headers)
    {
        $uri = $this->createUri($uri);
        if ('' === $uri->getHost()) {
            // Add base URI host
            $uri = $uri->withHost($this->hostname);
        }

        if (!$uri->getPort()) {
            $uri = $uri->withPort($this->port);
        }

        if ('' === $uri->getScheme()) {
            $uri = $uri->withScheme('http');
        }

        return MessageFactoryDiscovery::find()->createRequest(
            $method,
            $uri,
            $headers
        );
    }

    /**
     * Create PSR-7 URI object from URI string.
     *
     * @param string $uriString
     *
     * @return UriInterface
     */
    private function createUri($uriString)
    {
        return UriFactoryDiscovery::find()->createUri($uriString);
    }
}
