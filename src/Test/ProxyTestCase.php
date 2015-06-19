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

use FOS\HttpCache\Test\PHPUnit\IsCacheHitConstraint;
use FOS\HttpCache\Test\PHPUnit\IsCacheMissConstraint;
use Http\Adapter\HttpAdapter;
use Http\Discovery\HttpAdapterDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Abstract caching proxy test case
 *
 */
abstract class ProxyTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * HTTP adapter for requests to the application
     *
     * @var HttpAdapter
     */
    protected $httpAdapter;

    /**
     * Assert a cache miss
     *
     * @param ResponseInterface $response
     * @param string            $message  Test failure message (optional)
     */
    public function assertMiss(ResponseInterface $response, $message = null)
    {
        self::assertThat($response, self::isCacheMiss(), $message);
    }

    /**
     * Assert a cache hit
     *
     * @param ResponseInterface $response
     * @param string            $message  Test failure message (optional)
     */
    public function assertHit(ResponseInterface $response, $message = null)
    {
        self::assertThat($response, self::isCacheHit(), $message);
    }

    public static function isCacheHit()
    {
        return new IsCacheHitConstraint();
    }

    public static function isCacheMiss()
    {
        return new IsCacheMissConstraint();
    }

    /**
     * Get HTTP response from your application
     *
     * @param string $uri     HTTP URI
     * @param array  $headers HTTP headers
     * @param string $method  HTTP method
     *
     * @return ResponseInterface
     */
    public function getResponse($uri, array $headers = [], $method = 'GET')
    {
        // Close connections to make sure invalidation (PURGE/BAN) requests will
        // not interfere with content (GET) requests.
        $headers['Connection'] = 'Close';
        $request = $this->createRequest($method, $uri, $headers);

        return $this->getHttpAdapter()->sendRequest($request);
    }

    /**
     * Get HTTP adapter for your application
     *
     * @return HttpAdapter
     */
    protected function getHttpAdapter()
    {
        if ($this->httpAdapter === null) {
            $this->httpAdapter = HttpAdapterDiscovery::find();
        }

        return $this->httpAdapter;
    }

    /**
     * Start the proxy server and reset any cached content
     */
    protected function setUp()
    {
        $this->getProxy()->clear();
    }

    /**
     * Stop the proxy server
     */
    protected function tearDown()
    {
        $this->getProxy()->stop();
    }

    /**
     * Get the hostname where your application can be reached
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getHostName()
    {
        if (!defined('WEB_SERVER_HOSTNAME')) {
            throw new \Exception('To use this test, you need to define the WEB_SERVER_HOSTNAME constant in your phpunit.xml');
        }

        return WEB_SERVER_HOSTNAME;
    }

    /**
     * Create a request
     *
     * @param string $method
     * @param string $uri
     * @param array  $headers
     *
     * @return RequestInterface
     * @throws \Exception
     */
    protected function createRequest($method, $uri, $headers)
    {
        $uri = $this->createUri($uri);
        if ($uri->getHost() === '') {
            // Add base URI host
            $uri = $uri->withHost($this->getHostName());
        }

        if (!$uri->getPort()) {
            $uri = $uri->withPort($this->getCachingProxyPort());
        }

        if ($uri->getScheme() === '') {
            $uri = $uri->withScheme('http');
        }

        return MessageFactoryDiscovery::find()->createRequest(
            $method,
            $uri,
            '1.1',
            $headers
        );
    }

    /**
     * Create PSR-7 URI object from URI string
     *
     * @param string $uriString
     *
     * @return UriInterface
     */
    protected function createUri($uriString)
    {
        return UriFactoryDiscovery::find()->createUri($uriString);
    }

    /**
     * Get proxy server
     *
     * @return \FOS\HttpCache\Test\Proxy\ProxyInterface
     */
    abstract protected function getProxy();

    /**
     * Get proxy client
     *
     * @return \FOS\HttpCache\ProxyClient\ProxyClientInterface
     */
    abstract protected function getProxyClient();

    /**
     * Get port that caching proxy listens on
     *
     * @return int
     */
    abstract protected function getCachingProxyPort();
}
