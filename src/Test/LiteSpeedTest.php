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

use FOS\HttpCache\ProxyClient\HttpDispatcher;
use FOS\HttpCache\ProxyClient\LiteSpeed;
use FOS\HttpCache\Test\Proxy\LiteSpeedProxy;

// TODO: update docs in case anything is configurable
/**
 * Starts and clears the LiteSpeed proxy between tests.
 */
trait LiteSpeedTest
{
    /**
     * @var LiteSpeed
     */
    protected $proxyClient;

    /**
     * @var LiteSpeedProxy
     */
    protected $proxy;

    protected function setUp()
    {
        $this->getProxy()->clear();
    }

    protected function tearDown()
    {
        $this->getProxy()->stop();
    }

    /**
     * Defaults to "/usr/local/lsws/bin/lswsctrl".
     *
     * @return string
     */
    protected function getBinary()
    {
        return defined('LITESPEED_BINARY') ? LITESPEED_BINARY : null;
    }

    /**
     * Defaults to 80.
     *
     * @return int
     */
    protected function getCachingProxyPort()
    {
        return defined('LITESPEED_PORT') ? LITESPEED_PORT : 8088;
    }

    /**
     * Get the hostname where your application can be reached.
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getHostName()
    {
        // @codeCoverageIgnoreStart
        if (!defined('WEB_SERVER_HOSTNAME')) {
            throw new \Exception(
                'To use this test, you need to define the WEB_SERVER_HOSTNAME constant in your phpunit.xml'
            );
        }
        // @codeCoverageIgnoreEnd

        return WEB_SERVER_HOSTNAME;
    }

    /**
     * @return LiteSpeedProxy
     */
    protected function getProxy()
    {
        if (null === $this->proxy) {
            $this->proxy = new LiteSpeedProxy();
            $this->proxy->setPort($this->getCachingProxyPort());

            if ($this->getBinary()) {
                $this->proxy->setBinary($this->getBinary());
            }
        }

        return $this->proxy;
    }

    /**
     * Get proxy client.
     *
     * @return LiteSpeed
     */
    protected function getProxyClient()
    {
        if (null === $this->proxyClient) {
            $httpDispatcher = new HttpDispatcher(
                ['http://'.$this->getHostName().':'.$this->getCachingProxyPort()]
            );

            $this->proxyClient = new LiteSpeed($httpDispatcher);
        }

        return $this->proxyClient;
    }
}
