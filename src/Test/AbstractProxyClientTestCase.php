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
use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

/**
 * Abstract caching proxy test case
 *
 */
abstract class AbstractProxyClientTestCase extends \PHPUnit_Framework_TestCase implements ProxyTestCaseInterface
{
    /**
     * A Guzzle HTTP client.
     *
     * @var Client
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    public function assertMiss(Response $response, $message = null)
    {
        self::assertThat($response, self::isCacheMiss(), $message);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getResponse($url, array $headers = array(), $options = array())
    {
        return $this->getClient()->get($url, $headers, $options)->send();
    }

    /**
     * {@inheritdoc}
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
     * @return \FOS\HttpCache\Test\Proxy\ProxyInterface
     */
    abstract protected function getProxy();

    /**
     * @return \FOS\HttpCache\ProxyClient\ProxyClientInterface
     */
    abstract protected function getProxyClient();
}
