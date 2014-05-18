<?php

namespace FOS\HttpCache\Tests;

use FOS\HttpCache\ProxyClient\Nginx;
use Guzzle\Http\Message\Response;

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
abstract class NginxTestCase extends AbstractProxyClientTestCase
{
    /**
     * @var Nginx
     */
    protected $nginx;

    const CACHE_EXPIRED = 'EXPIRED';

    const PID = '/tmp/foshttpcache-nginx.pid';

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
        $configFile = NGINX_FILE;

        if (!file_exists($configFile)) {
            throw new \Exception('Can not find specified nginx config file: ' . $configFile);
        }

	    $configFile = __DIR__."/../".$configFile;

        return $configFile;
    }

    /**
     * Defaults to "nginx"
     *
     * @return string
     */
    protected function getBinary()
    {
        return defined('NGINX_BINARY') ? NGINX_BINARY : 'nginx';
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
     * Get NGINX cache path
     */
    protected function getCacheDir()
    {
        if (!defined('NGINX_CACHE_PATH')) {
            throw new \Exception('Specify the NGINX_CACHE_PATH in phpunit.xml or override getCacheDir()');
        }

        return NGINX_CACHE_PATH;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNginx($purgeLocation = false)
    {
        if (null === $this->nginx) {
            $this->nginx = new Nginx(
                array('http://127.0.0.1:' . $this->getCachingProxyPort()),
                $this->getHostName(),
                $purgeLocation
            );
        }

        return $this->nginx;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->stopNginx();
    }

    /**
     * Stop Nginx process if it's running.
     */
    protected function stopNginx()
    {
        if (file_exists(self::PID)) {
            exec('kill ' . file_get_contents(self::PID));
        }
    }

    /**
     * Start Nginx process if it's not yet running.
     */
    protected function startNginx()
    {
        exec($this->getBinary() .
            ' -c ' . $this->getConfigFile() .
            ' -g "pid ' . self::PID . ';"'
        );

        $this->waitFor('127.0.0.1', $this->getCachingProxyPort(), 2000);
    }

    protected function resetProxyDaemon()
    {
        $this->clearCache();
        $this->startNginx();
    }

    /**
     * Clear Nginx cache
     */
    protected function clearCache()
    {
        exec('rm -rf ' . $this->getCacheDir()."*");
    }
}
