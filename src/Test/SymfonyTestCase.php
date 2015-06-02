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

use FOS\HttpCache\ProxyClient\Symfony;
use FOS\HttpCache\Test\Proxy\SymfonyProxy;

/**
 * A phpunit base class to write functional tests with the symfony HttpCache.
 *
 * The webserver with symfony is to be started with the WebServerListener.
 *
 * You can define constants in your phpunit to control how this test behaves.
 *
 * To define constants in the phpunit file, use this syntax:
 * <php>
 *     <const name="WEB_SERVER_PORT" value="8080" />
 * </php>
 *
 * WEB_SERVER_PORT     port the PHP webserver listens on (required)
 * WEB_SERVER_HOSTNAME hostname where your application can be reached (required)
 *
 * Note that the SymfonyProxy also uses a SYMFONY_CACHE_DIR constant.
 */
abstract class SymfonyTestCase extends ProxyTestCase
{
    /**
     * @var Symfony
     */
    protected $proxyClient;

    /**
     * @var SymfonyProxy
     */
    protected $proxy;

    /**
     * Get server port
     *
     * @return int
     *
     * @throws \Exception
     */
    protected function getCachingProxyPort()
    {
        if (!defined('WEB_SERVER_PORT')) {
            throw new \Exception('Set WEB_SERVER_PORT in your phpunit.xml');
        }

        return WEB_SERVER_PORT;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProxy()
    {
        if (null === $this->proxy) {
            $this->proxy = new SymfonyProxy();
        }

        return $this->proxy;
    }

    /**
     * Get Symfony proxy client
     *
     * We use a non-default method for PURGE because the built-in PHP webserver
     * does not allow arbitrary HTTP methods.
     * https://github.com/php/php-src/blob/PHP-5.4.1/sapi/cli/php_http_parser.c#L78-L102
     *
     * @return Symfony
     */
    protected function getProxyClient()
    {
        if (null === $this->proxyClient) {
            $this->proxyClient = new Symfony(
                array('http://127.0.0.1:' . $this->getCachingProxyPort()),
                $this->getHostName() . ':' . $this->getCachingProxyPort(),
                null,
                array('purge_method' => 'NOTIFY')
            );
        }

        return $this->proxyClient;
    }
}
