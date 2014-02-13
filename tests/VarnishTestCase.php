<?php

namespace FOS\HttpCache\Tests;

use FOS\HttpCache\Invalidation\Varnish;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

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
abstract class VarnishTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * A guzzle http client.
     *
     * @var Client
     */
    private static $client;

    /**
     * @var Varnish
     */
    protected $varnish;

    /**
     * Name of the debug header varnish is sending to tell if the request was a
     * hit or miss.
     *
     * @var string
     */
    const CACHE_HEADER = 'X-Cache';

    const CACHE_MISS = 'MISS';
    const CACHE_HIT  = 'HIT';

    const PID = '/tmp/foshttpcache-varnish.pid';

    protected static $binary;
    protected static $port;
    protected static $mgmtPort;
    protected static $configFile;
    protected static $cacheDir;
    protected static $hostName;

    protected function readConfiguration($configFile = null)
    {
        if (!self::$binary) {
            self::$binary = defined('VARNISH_BINARY') ? VARNISH_BINARY : 'varnishd';
        }
        if (!self::$port) {
            self::$port = defined('VARNISH_PORT') ? VARNISH_PORT : 6181;
        }
        if (!self::$mgmtPort) {
            self::$mgmtPort = defined('VARNISH_MGMT_PORT') ? VARNISH_MGMT_PORT : 6182;
        }

        if ($configFile) {
            self::$configFile = $configFile;
        }
        if (!self::$configFile) {
            if (!defined('VARNISH_FILE')) {
                throw new \Exception('Either specify the varnish configuration file path in phpunit.xml or pass it as argument to the setUp method');
            }
            self::$configFile = VARNISH_FILE;
        }
        if (!file_exists(self::$configFile)) {
            throw new \Exception('Can not find specified varnish config file: ' . self::$configFile);
        }

        if (!self::$cacheDir) {
            self::$cacheDir = defined('VARNISH_CACHE_DIR') ? VARNISH_CACHE_DIR : '/tmp/foshttpcache-test';
        }
        if (!self::$hostName) {
            if (!defined('WEB_SERVER_HOSTNAME')) {
                throw new \Exception('To use this test, you need to define the WEB_SERVER_HOSTNAME constant in your phpunit.xml');
            }
            self::$hostName = WEB_SERVER_HOSTNAME;
        }
    }

    /**
     * @param string|null $configFile The varnish configuration file.
     *      Overwrites the VARNISH_FILE constant.
     */
    public function setUp($configFile = null)
    {
        $this->readConfiguration($configFile);

        $this->varnish = new Varnish(
            array('http://127.0.0.1:' . self::$port),
            self::$hostName . ':' . self::$port
        );

        if (file_exists(self::PID)) {
            exec('kill ' . file_get_contents(self::PID));
            unlink(self::PID);
        }
        exec(self::$binary .
            ' -a localhost:' . self::$port .
            ' -T localhost:' . self::$mgmtPort .
            ' -f ' . self::$configFile .
            ' -n ' . self::$cacheDir .
            ' -P ' . self::PID
        );

        $this->waitForVarnish('127.0.0.1', self::$port, 2000);
    }

    public function tearDown()
    {
        if (file_exists(self::PID)) {
            exec('kill ' . file_get_contents(self::PID));
            unlink(self::PID);
        }
    }

    public static function getClient()
    {
        if (null === self::$client) {
            self::$client = new Client('http://' . self::$hostName . ':' . self::$port);
        }

        return self::$client;
    }

    public static function getResponse($url, array $headers = array())
    {
        return self::getClient()->get($url, $headers)->send();
    }

    public function assertMiss(Response $response, $message = null)
    {
        $this->assertEquals(self::CACHE_MISS, (string) $response->getHeader(self::CACHE_HEADER), $message);
    }

    public function assertHit(Response $response, $message = null)
    {
        $this->assertEquals(self::CACHE_HIT, (string) $response->getHeader(self::CACHE_HEADER), $message);
    }

    /**
     * Wait for Varnish proxy to be started up and reachable
     *
     * @param string $ip
     * @param int    $port
     * @param int    $timeout Timeout in milliseconds
     *
     * @throws \RuntimeException If Varnish is not reachable within timeout
     */
    protected function waitForVarnish($ip, $port, $timeout)
    {
        for ($i = 0; $i < $timeout; $i++) {
            if (@fsockopen($ip, $port)) {
                return;
            }

            usleep(1000);
        }

        throw new \RuntimeException(sprintf('Varnish proxy cannot be reached at %s:%s', '127.0.0.1', self::$port));
    }
}
