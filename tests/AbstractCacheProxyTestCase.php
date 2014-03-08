<?php

namespace FOS\HttpCache\Tests;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

/**
 * Abstract caching proxy test case
 *
 */
abstract class AbstractCacheProxyTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Name of the debug header varnish is sending to tell if the request was a
     * hit or miss.
     *
     * @var string
     */
    const CACHE_HEADER = 'X-Cache';

    const CACHE_MISS = 'MISS';
    const CACHE_HIT  = 'HIT';

    /**
     * A guzzle http client.
     *
     * @var Client
     */
    private static $client;

    /**
     * Assert a cache miss
     *
     * @param Response $response
     * @param string   $message  Test failure message (optional)
     */
    public function assertMiss(Response $response, $message = null)
    {
        $this->assertEquals(self::CACHE_MISS, (string) $response->getHeader(self::CACHE_HEADER), $message);
    }

    /**
     * Assert a cache hit
     *
     * @param Response $response
     * @param string   $message  Test failure message (optional)
     */
    public function assertHit(Response $response, $message = null)
    {
        $this->assertEquals(self::CACHE_HIT, (string) $response->getHeader(self::CACHE_HEADER), $message);
    }

    /**
     * Get HTTP response from your application
     *
     * @param string $url
     * @param array  $headers
     *
     * @return Response
     */
    public function getResponse($url, array $headers = array())
    {
        return $this->getClient()->get($url, $headers)->send();
    }

    /**
     * Get HTTP client for your application
     *
     * @return Client
     */
    public function getClient()
    {
        if (null === self::$client) {
            self::$client = new Client('http://' . $this->getHostName() . ':' . $this->getCachingProxyPort());
        }

        return self::$client;
    }

    /**
     * Clear the cache between each test
     */
    protected function setUp()
    {
        $this->clearCache();
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
     * Wait for caching proxy to be started up and reachable
     *
     * @param string $ip
     * @param int    $port
     * @param int    $timeout Timeout in milliseconds
     *
     * @throws \RuntimeException If proxy is not reachable within timeout
     */
    protected function waitFor($ip, $port, $timeout)
    {
        for ($i = 0; $i < $timeout; $i++) {
            if (@fsockopen($ip, $port)) {
                return;
            }

            usleep(1000);
        }

        throw new \RuntimeException(
            sprintf(
                'Caching proxy cannot be reached at %s:%s',
                $ip,
                $this->getCachingProxyPort()
            )
        );
    }

    /**
     * Get port at which the caching proxy is running
     *
     * @return int
     */
    abstract protected function getCachingProxyPort();

    /**
     * Clear the cache
     */
    abstract protected function clearCache();
}
