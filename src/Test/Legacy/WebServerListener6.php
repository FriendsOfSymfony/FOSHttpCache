<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test\Legacy;

use FOS\HttpCache\Test\WebServerListenerTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;

/**
 * A PHPUnit test listener that starts and stops the PHP built-in web server.
 *
 * This legacy version is for PHPUnit 6.x.
 *
 * This listener is configured with a couple of constants from the phpunit.xml
 * file. To define constants in the phpunit file, use this syntax:
 * <php>
 *     <const name="WEB_SERVER_HOSTNAME" value="localhost" />
 * </php>
 *
 * WEB_SERVER_HOSTNAME host name of the web server (required)
 * WEB_SERVER_PORT     port to listen on (required)
 * WEB_SERVER_DOCROOT  path to the document root for the server (required)
 */
class WebServerListener6 implements TestListener
{
    /** @var WebServerListenerTrait */
    private $trait;

    public function __construct()
    {
        $this->trait = new WebServerListenerTrait();
    }

    /**
     * Make sure the PHP built-in web server is running for tests with group
     * 'webserver'.
     */
    public function startTestSuite(TestSuite $suite)
    {
        $this->trait->startTestSuite($suite);
    }

    /**
     *  We don't need these.
     */
    public function endTestSuite(TestSuite $suite)
    {
    }

    public function addError(Test $test, \Exception $e, $time)
    {
    }

    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
    }

    public function addIncompleteTest(Test $test, \Exception $e, $time)
    {
    }

    public function addSkippedTest(Test $test, \Exception $e, $time)
    {
    }

    public function startTest(Test $test)
    {
    }

    public function endTest(Test $test, $time)
    {
    }

    public function addRiskyTest(Test $test, \Exception $e, $time)
    {
    }

    public function addWarning(Test $test, Warning $e, $time)
    {
    }

    /**
     * Get web server hostname.
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getHostName()
    {
        return $this->trait->getHostName();
    }

    /**
     * Get web server port.
     *
     * @return int
     *
     * @throws \Exception
     */
    protected function getPort()
    {
        return $this->trait->getPort();
    }

    /**
     * Get web server port.
     *
     * @return int
     *
     * @throws \Exception
     */
    protected function getDocRoot()
    {
        return $this->trait->getDocRoot();
    }

    /**
     * Start PHP built-in web server.
     *
     * @return int PID
     */
    protected function startPhpWebServer()
    {
        return $this->trait->startPhpWebServer();
    }

    /**
     * Wait for caching proxy to be started up and reachable.
     *
     * @param string $ip
     * @param int    $port
     * @param int    $timeout Timeout in milliseconds
     *
     * @throws \RuntimeException If proxy is not reachable within timeout
     */
    protected function waitFor($ip, $port, $timeout)
    {
        $this->trait->waitFor($ip, $port, $timeout);
    }
}
