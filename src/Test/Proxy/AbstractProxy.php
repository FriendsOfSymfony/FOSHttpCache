<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test\Proxy;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractProxy implements ProxyInterface
{
    protected $port;

    protected $binary;

    protected $configFile;

    protected $ip = '127.0.0.1';

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
        if (!$this->wait(
            $timeout,
            function () use ($ip, $port) {
                return false !== @fsockopen($ip, $port);
            }
        )) {
            throw new \RuntimeException(
                sprintf(
                    'Caching proxy cannot be reached at %s:%s',
                    $ip,
                    $port
                )
            );
        }
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
    protected function waitUntil($ip, $port, $timeout)
    {
        if (!$this->wait(
            $timeout,
            function () use ($ip, $port) {
                return false === @fsockopen($ip, $port);
            }
        )) {
            throw new \RuntimeException(
                sprintf(
                    'Caching proxy still up at %s:%s',
                    $ip,
                    $port
                )
            );
        }
    }

    protected function wait($timeout, $callback)
    {
        for ($i = 0; $i < $timeout; ++$i) {
            if ($callback()) {
                return true;
            }

            usleep(1000);
        }

        return false;
    }

    /**
     * Run a shell command.
     *
     * @param array $command
     * @param bool  $sudo
     *
     * @throws ProcessFailedException If command execution fails
     */
    protected function runCommand(array $command, $sudo = false)
    {
        if ($sudo) {
            $command = array_merge(['sudo'], $command);
        }

        $process = new Process($command);
        $process->mustRun();
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set Varnish binary (defaults to varnishd).
     *
     * @param string $binary
     */
    public function setBinary($binary)
    {
        $this->binary = $binary;
    }

    /**
     * Get Varnish binary.
     *
     * @return string
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * Set IP address (defaults to 127.0.0.1).
     *
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get IP address.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $configFile
     *
     * @throws \InvalidArgumentException
     */
    public function setConfigFile($configFile)
    {
        if (!file_exists($configFile)) {
            throw new \InvalidArgumentException('Cannot find config file: '.$configFile);
        }

        $this->configFile = $configFile;
    }

    /**
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }
}
