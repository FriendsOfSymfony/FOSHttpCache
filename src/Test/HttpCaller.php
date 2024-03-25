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

use Psr\Http\Message\ResponseInterface;

/**
 * Provides a method for getting responses from your application.
 */
trait HttpCaller
{
    /**
     * HTTP client for requests to the application.
     */
    private HttpClient $httpClient;

    /**
     * Call a HTTP resource from your test.
     *
     * @param string                $uri     HTTP URI, domain and port are added from the embedding class if not specified
     * @param array<string, string> $headers HTTP headers
     */
    protected function getResponse(string $uri, array $headers = [], string $method = 'GET'): ResponseInterface
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = new HttpClient($this->getHostName(), $this->getCachingProxyPort());
        }

        return $this->httpClient->getResponse($uri, $headers, $method);
    }

    /**
     * Get the default host name to use.
     */
    abstract protected function getHostName(): string;

    /**
     * Get the default port to use.
     */
    abstract protected function getCachingProxyPort(): int;
}
