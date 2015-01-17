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
 * You can define constants in your phpunit.xml to control how this test behaves.
 *
 * Note that the WEB_SERVER_HOSTNAME must also match with what you have in your
 * NGINX configuration file.
 *
 * To define constants in the phpunit file, use this syntax:
 * <php>
 *     <const name="NGINX_FILE" value="./tests/FOS/HttpCache/Tests/Functional/Fixtures/nginx/fos.conf" />
 * </php>
 *
 * NGINX_FILE          NGINX configuration file (required if not passed to setUp)
 * NGINX_BINARY        Executable for NGINX. This can also be the full path
 *                     to the file if the binary is not automatically found
 *                     (default nginx)
 * NGINX_PORT          Port NGINX listens to (default 8088)
 * NGINX_CACHE_PATH    directory to use for cache
 *                     Must match `proxy_cache_path` directive in
 *                     your configuration file.
 *                     (default sys_get_temp_dir() + '/foshttpcache-nginx')
 * WEB_SERVER_HOSTNAME hostname where your application can be reached (required)
 */
abstract class NginxTestCase extends ProxyTestCase
{
    /**
     * @var Nginx
     */
    protected $proxyClient;

    /**
     * @var NginxProxy
     */
    protected $proxy;

    /**
     * The default implementation looks at the constant NGINX_FILE.
     *
     * @throws \Exception
     *
     * @return string the path to the NGINX server configuration file to use with this test.
     */
    protected function getConfigFile()
    {
        if (!defined('NGINX_FILE')) {
            throw new \Exception('Specify the NGINX'
                    . ' configuration file path in phpunit.xml or override getConfigFile()');
        }

        // NGINX needs an absolute path
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
     * Get NGINX cache directory
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
