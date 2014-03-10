<?php

namespace FOS\HttpCache\Tests;

use FOS\HttpCache\Invalidation\Varnish;

/**
 * A phpunit base class to write functional tests with varnish.
 *
 * You can define a couple of constants in your phpunit to control how this
 * test behaves.
 *
 * Note that the WEB_SERVER_HOSTNAME must also match with what you have in your
 * .vcl file.
 *
 * To define constants in the phpunit file, use this syntax:
 * <php>
 *     <const name="VARNISH_FILE" value="./tests/FOS/HttpCache/Tests/Functional/Fixtures/varnish/fos.vcl" />
 * </php>
 *
 * VARNISH_BINARY       executable for varnish. this can also be the full path
 *                      to the file if the binary is not automatically found
 *                      (default varnishd)
 * VARNISH_PORT         test varnish port to use (default 6181)
 * VARNISH_MGMT_PORT    test varnish mgmt port (default 6182)
 * VARNISH_FILE         varnish configuration file (required if not passed to setUp)
 * VARNISH_CACHE_DIR    directory to use for cache
 *                      (default /tmp/foshttpcache-test)
 * WEB_SERVER_HOSTNAME  name of the webserver varnish has to talk to (required)
 */
abstract class VarnishTestCase extends AbstractCacheProxyTestCase
{
    /**
     * @var Varnish
     */
    protected $varnish;

    const PID = '/tmp/foshttpcache-varnish.pid';

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
        $configFile = VARNISH_FILE;

        if (!file_exists($configFile)) {
            throw new \Exception('Can not find specified varnish config file: ' . $configFile);
        }

        return $configFile;
    }

    /**
     * Get directory that holds VCL files
     *
     * Defaults to the directory that the VCL config file is in.
     *
     * @return string
     */
    protected function getConfigDir()
    {
        return dirname(realpath($this->getConfigFile()));
    }

    /**
     * Defaults to "varnishd"
     *
     * @return string
     */
    protected function getBinary()
    {
        return defined('VARNISH_BINARY') ? VARNISH_BINARY : 'varnishd';
    }

    /**
     * Defaults to 6181, the varnish default.
     *
     * @return int
     */
    protected function getCachingProxyPort()
    {
        return defined('VARNISH_PORT') ? VARNISH_PORT : 6181;
    }

    /**
     * Defaults to 6182, the varnish default.
     *
     * @return int
     */
    protected function getVarnishMgmtPort()
    {
        return defined('VARNISH_MGMT_PORT') ? VARNISH_MGMT_PORT : 6182;
    }

    /**
     * Defaults to a directory foshttpcache-test in the system tmp directory.
     *
     * @return string
     */
    protected function getCacheDir()
    {
        return defined('VARNISH_CACHE_DIR') ? VARNISH_CACHE_DIR : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'foshttpcache-test';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->stopVarnish();
    }

    /**
     * Stop Varnish process if it's running
     */
    protected function stopVarnish()
    {
        if (file_exists(self::PID)) {
            exec('kill ' . file_get_contents(self::PID));
            unlink(self::PID);
        }
    }

    /**
     * Start Varnish process if it's not yet running
     */
    protected function startVarnish()
    {
        exec($this->getBinary() .
            ' -a localhost:' . $this->getCachingProxyPort() .
            ' -T localhost:' . $this->getVarnishMgmtPort() .
            ' -f ' . $this->getConfigFile() .
            ' -n ' . $this->getCacheDir() .
            ' -p vcl_dir=' . $this->getConfigDir() .
            ' -P ' . self::PID
        );

        $this->waitFor('127.0.0.1', $this->getCachingProxyPort(), 2000);
    }

    /**
     * {@inheritdoc}
     */
    protected function clearCache()
    {
        // Clear Varnish cache by restarting
        $this->stopVarnish();
        $this->startVarnish();
    }

    protected function getVarnish()
    {
        if (null === $this->varnish) {
            $this->varnish = new Varnish(
                array('http://127.0.0.1:' . $this->getCachingProxyPort()),
                $this->getHostName() . ':' . $this->getCachingProxyPort()
            );
        }

        return $this->varnish;
    }
}
