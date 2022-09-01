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

/**
 * This fake trait is used to have the same code and behavior between WebServerListener and its legacy version.
 */
class WebServerListenerTrait
{
    /**
     * PHP web server PID.
     *
     * @var int
     */
    protected $pid;

    /**
     * Make sure the PHP built-in web server is running for tests with group
     * 'webserver'.
     */
    public function startTestSuite($suite)
    {
        // Only run on PHP >= 5.4 as PHP below that and HHVM don't have a
        // built-in web server
        if (defined('HHVM_VERSION')) {
            return;
        }

        if (null !== $this->pid || !in_array('webserver', $suite->getGroups())) {
            return;
        }

        $this->pid = $pid = $this->startPhpWebServer();

        register_shutdown_function(function () use ($pid) {
            exec('kill '.$pid);
        });
    }

    /**
     * Get web server hostname.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getHostName()
    {
        if (!defined('WEB_SERVER_HOSTNAME')) {
            throw new \Exception('Set WEB_SERVER_HOSTNAME in your phpunit.xml');
        }

        return WEB_SERVER_HOSTNAME;
    }

    /**
     * Get web server port.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getPort()
    {
        if (!defined('WEB_SERVER_PORT')) {
            throw new \Exception('Set WEB_SERVER_PORT in your phpunit.xml');
        }

        return WEB_SERVER_PORT;
    }

    /**
     * Get web server port.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getDocRoot()
    {
        if (!defined('WEB_SERVER_DOCROOT')) {
            throw new \Exception('Set WEB_SERVER_DOCROOT in your phpunit.xml');
        }

        return WEB_SERVER_DOCROOT;
    }

    /**
     * Start PHP built-in web server.
     *
     * @return int PID
     */
    public function startPhpWebServer()
    {
        $command = sprintf(
            'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
            '127.0.0.1',
            $this->getPort(),
            $this->getDocRoot()
        );
        exec($command, $output);

        $this->waitFor($this->getHostName(), $this->getPort(), 2000);

        return $output[0];
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
    public function waitFor($ip, $port, $timeout)
    {
        for ($i = 0; $i < $timeout; ++$i) {
            if (@fsockopen($ip, $port)) {
                return;
            }

            usleep(1000);
        }

        throw new \RuntimeException(
            sprintf(
                'Webserver cannot be reached at %s:%s',
                $ip,
                $port
            )
        );
    }
}
