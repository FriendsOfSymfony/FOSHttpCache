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

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
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
    private ClientInterface $httpClient;

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
    private function getHttpClient(): ClientInterface
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = Psr18ClientDiscovery::find();
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

        $request = Psr17FactoryDiscovery::findRequestFactory()->createRequest(
            $method,
            $uri
        );
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    /**
     * Create PSR-7 URI object from URI string.
     */
    private function createUri(string $uriString): UriInterface
    {
        return Psr17FactoryDiscovery::findUriFactory()->createUri($uriString);
    }
}
