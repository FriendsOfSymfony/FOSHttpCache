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
     */
    private PhpHttpClient $httpClient;

    private string $hostname;

    private string $port;

    /**
     * @param string $hostname Default hostname if not specified in the URL
     * @param string $port     Default port if not specified in the URL
     */
    public function __construct(string $hostname, string $port)
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
     */
    public function getResponse(string $uri, array $headers, string $method): ResponseInterface
    {
        $request = $this->createRequest($method, $uri, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Send PSR HTTP request to your application.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Close connections to make sure invalidation (PURGE/BAN) requests will
        // not interfere with content (GET) requests.
        $request = $request->withHeader('Connection', 'Close');

        return $this->getHttpClient()->sendRequest($request);
    }

    /**
     * Get HTTP client for your application.
     */
    private function getHttpClient(): PhpHttpClient
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = HttpClientDiscovery::find();
        }

        return $this->httpClient;
    }

    /**
     * @param array<string, string> $headers
     *
     * @throws \Exception
     */
    private function createRequest(string $method, string $uri, array $headers): RequestInterface
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
     */
    private function createUri(string $uriString): UriInterface
    {
        return UriFactoryDiscovery::find()->createUri($uriString);
    }
}
