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

use FOS\HttpCache\ProxyClient\Varnish;
use FOS\HttpCache\Test\Proxy\VarnishProxy;

/**
 * A phpunit base class to write functional tests with varnish.
 *
 * You can define constants in your phpunit to control how this test behaves.
 *
 * Note that the WEB_SERVER_HOSTNAME must also match with what you have in your
 * .vcl file.
 *
 * To define constants in the phpunit file, use this syntax:
 * <php>
 *     <const name="VARNISH_FILE" value="./tests/Functional/Fixtures/varnish-3/fos.vcl" />
 * </php>
 *
 * VARNISH_FILE         Varnish configuration file (required if not passed to setUp)
 * VARNISH_BINARY       executable for Varnish. this can also be the full path
 *                      to the file if the binary is not automatically found
 *                      (default varnishd)
 * VARNISH_PORT         port Varnish listens on (default 6181)
 * VARNISH_MGMT_PORT    Varnish management port (default 6182)
 * VARNISH_CACHE_DIR    directory to use for cache
 *                      (default sys_get_temp_dir() + '/foshttpcache-varnish')
 * VARNISH_VERSION      Version of varnish: 3 or 4, defaults to 3.
 * WEB_SERVER_HOSTNAME  hostname/IP your application can be reached at (required)
 */
abstract class VarnishTestCase extends ProxyTestCase
{
    /**
     * @var Varnish
     */
    protected $proxyClient;

    /**
     * @var VarnishProxy
     */
    protected $proxy;

    /**
     * The default implementation looks at the constant VARNISH_FILE.
     *
     * @throws \Exception
     *
     * @return string the path to the varnish server configuration file to use with this test.
     */
    protected function getConfigFile()
    {
        if (!defined('VARNISH_FILE')) {
            throw new \Exception('Specify the varnish configuration file path in phpunit.xml or override getConfigFile()');
        }

        return VARNISH_FILE;
    }

    /**
     * Get Varnish binary
     *
     * @return string
     */
    protected function getBinary()
    {
        return defined('VARNISH_BINARY') ? VARNISH_BINARY : null;
    }

    /**
     * Get Varnish port
     *
     * @return int
     */
    protected function getCachingProxyPort()
    {
        return defined('VARNISH_PORT') ? VARNISH_PORT : 6181;
    }

    /**
     * Get Varnish management port
     *
     * @return int
     */
    protected function getVarnishMgmtPort()
    {
        return defined('VARNISH_MGMT_PORT') ? VARNISH_MGMT_PORT : null;
    }

    /**
     * Get Varnish cache directory
     *
     * @return string
     */
    protected function getCacheDir()
    {
        return defined('VARNISH_CACHE_DIR') ? VARNISH_CACHE_DIR : null;
    }

    /**
     * Defaults to 3
     *
     * @return int
     */
    protected function getVarnishVersion()
    {
        return getenv('VARNISH_VERSION') ?: 3;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProxy()
    {
        if (null === $this->proxy) {
            $this->proxy = new VarnishProxy($this->getConfigFile());
            if ($this->getBinary()) {
                $this->proxy->setBinary($this->getBinary());
            }

            if ($this->getCachingProxyPort()) {
                $this->proxy->setPort($this->getCachingProxyPort());
            }

            if ($this->getVarnishMgmtPort()) {
                $this->proxy->setManagementPort($this->getVarnishMgmtPort());
            }

            if ($this->getCacheDir()) {
                $this->proxy->setCacheDir($this->getCacheDir());
            }
        }

        return $this->proxy;
    }

    /**
     * Get Varnish proxy client
     *
     * @return Varnish
     */
    protected function getProxyClient()
    {
        if (null === $this->proxyClient) {
            $this->proxyClient = new Varnish(
                array('http://127.0.0.1:' . $this->getProxy()->getPort()),
                $this->getHostName() . ':' . $this->getProxy()->getPort()
            );
        }

        return $this->proxyClient;
    }
}
