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
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Call a HTTP resource from your test.
     *
     * @param string $uri     HTTP URI, domain and port are added from the embedding class if not specified
     * @param array  $headers HTTP headers
     * @param string $method  HTTP method
     *
     * @return ResponseInterface
     */
    protected function getResponse($uri, array $headers = [], $method = 'GET')
    {
        if (!$this->httpClient) {
            $this->httpClient = new HttpClient($this->getHostName(), $this->getCachingProxyPort());
        }

        return $this->httpClient->getResponse($uri, $headers, $method);
    }

    /**
     * Get the default host name to use.
     *
     * @return string
     */
    abstract protected function getHostName();

    /**
     * Get the default port to use.
     *
     * @return string
     */
    abstract protected function getCachingProxyPort();
}
