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

use FOS\HttpCache\ProxyClient\Nginx;
use FOS\HttpCache\Test\Proxy\NginxProxy;

/**
 * A phpunit base class to write functional tests with NGINX.
 *
 * You can define a couple of constants in your phpunit to control how this
 * test behaves.
 *
 * To define constants in the phpunit file, use this syntax:
 * <php>
 *     <const name="NGINX_FILE" value="./tests/FOS/HttpCache/Tests/Functional/Fixtures/nginx/fos.conf" />
 * </php>
 *
 * NGINX_BINARY       Executable for NGINX. This can also be the full path
 *                      to the file if the binary is not automatically found
 *                      (default nginx)
 * NGINX_PORT         Test NGINX port to use (default 8088)
 * NGINX_FILE         NGINX configuration file (required if not passed to setUp)
 * NGINX_CACHE_PATH   NGINX configuration file (required if not passed to setUp)
 */
abstract class NginxTestCase extends ProxyTestCase
{
    /**
     * @var NginxProxy
     */
    protected $proxy;

    /**
     * @var Nginx
     */
    protected $proxyClient;

    /**
     * The default implementation looks at the constant NGINX_FILE.
     *
     * @throws \Exception
     *
     * @return string the path to the nginx server configuration file to use with this test.
     */
    protected function getConfigFile()
    {
        if (!defined('NGINX_FILE')) {
            throw new \Exception('Specify the nginx configuration file path in phpunit.xml or override getConfigFile()');
        }

        // Nginx needs an absolute path
        return realpath(NGINX_FILE);
    }

    /**
     * Defaults to "nginx"
     *
     * @return string
     */
    protected function getBinary()
    {
        return defined('NGINX_BINARY') ? NGINX_BINARY : null;
    }

    /**
     * Defaults to 8088.
     *
     * @return int
     */
    protected function getCachingProxyPort()
    {
        return defined('NGINX_PORT') ? NGINX_PORT : 8088;
    }

    /**
     * Get Nginx cache directory
     */
    protected function getCacheDir()
    {
        return defined('NGINX_CACHE_PATH') ? NGINX_CACHE_PATH : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProxy()
    {
        if (null === $this->proxy) {
            $this->proxy = new NginxProxy($this->getConfigFile());
            $this->proxy->setPort($this->getCachingProxyPort());

            if ($this->getBinary()) {
                $this->proxy->setBinary($this->getBinary());
            }

            if ($this->getCacheDir()) {
                $this->proxy->setCacheDir($this->getCacheDir());
            }
        }

        return $this->proxy;
    }

    /**
     * Get proxy client
     *
     * @param string $purgeLocation Optional purgeLocation
     *
     * @return Nginx
     */
    protected function getProxyClient($purgeLocation = '')
    {
        if (null === $this->proxyClient) {
            $this->proxyClient = new Nginx(
                array('http://127.0.0.1:' . $this->getCachingProxyPort()),
                $this->getHostName(),
                $purgeLocation
            );
        }

        return $this->proxyClient;
    }
}
