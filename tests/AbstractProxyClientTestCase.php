<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests;

use FOS\HttpCache\Tests\PHPUnit\IsCacheHitConstraint;
use FOS\HttpCache\Tests\PHPUnit\IsCacheMissConstraint;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

/**
 * Abstract caching proxy test case
 *
 */
abstract class AbstractProxyClientTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * A Guzzle HTTP client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Assert a cache miss
     *
     * @param Response $response
     * @param string   $message  Test failure message (optional)
     */
    public function assertMiss(Response $response, $message = null)
    {
        self::assertThat($response, self::isCacheMiss(), $message);
    }

    /**
     * Assert a cache hit
     *
     * @param Response $response
     * @param string   $message  Test failure message (optional)
     */
    public function assertHit(Response $response, $message = null)
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
     * @param string $url
     * @param array  $headers
     * @param array  $options
     *
     * @return Response
     */
    public function getResponse($url, array $headers = array(), $options = array())
    {
        return $this->getClient()->get($url, $headers, $options)->send();
    }

    /**
     * Get HTTP client for your application
     *
     * @return Client
     */
    public function getClient()
    {
        if (null === $this->client) {
            $this->client = new Client(
                'http://' . $this->getHostName() . ':' . $this->getCachingProxyPort(),
                array('curl.options' => array(CURLOPT_FORBID_REUSE => true))
            );
        }

        return $this->client;
    }

    /**
     * Prepare the proxy daemon.
     */
    protected function setUp()
    {
        $this->resetProxyDaemon();
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
        if (!$this->wait(
            $timeout,
            function () use ($ip, $port) {
                return true == @fsockopen($ip, $port);
            }
        )) {
            throw new \RuntimeException(
                sprintf(
                    'Caching proxy cannot be reached at %s:%s',
                    $ip,
                    $port
                )
            );
        }
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
    protected function waitUntil($ip, $port, $timeout)
    {
        if (!$this->wait(
            $timeout,
            function () use ($ip, $port) {
                // This doesn't seem to work
                return false == @fsockopen($ip, $port);
            }
        )) {
            throw new \RuntimeException(
                sprintf(
                    'Caching proxy still up at %s:%s',
                    $ip,
                    $port
                )
            );
        }
    }

    protected function wait($timeout, $callback)
    {
        for ($i = 0; $i < $timeout; $i++) {
            if ($callback()) {
                return true;
            }

            usleep(1000);
        }

        return false;
    }

    /**
     * Get port at which the caching proxy is running
     *
     * @return int
     */
    abstract protected function getCachingProxyPort();

    /**
     * Ensure the daemon is running and its cache is clear.
     */
    abstract protected function resetProxyDaemon();

    /**
     * Clear the cache
     */
    abstract protected function clearCache();
}
