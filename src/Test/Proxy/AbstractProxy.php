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

use Symfony\Component\Process\Process;

abstract class AbstractProxy implements ProxyInterface
{
    protected int $port;

    protected string $binary;

    protected string $configFile;

    protected string $ip = '127.0.0.1';

    /**
     * Wait for caching proxy to be started up and reachable.
     *
     * @param int $timeout Timeout in milliseconds
     *
     * @throws \RuntimeException If proxy is not reachable within timeout
     */
    protected function waitFor(string $ip, int $port, int $timeout): void
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
     * @param int $timeout Timeout in milliseconds
     *
     * @throws \RuntimeException If proxy is not reachable within timeout
     */
    protected function waitUntil(string $ip, int $port, int $timeout): void
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

    protected function wait(int $timeout, callable $callback): bool
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
     * @throws \RuntimeException If command execution fails
     */
    protected function runCommand(string $command, array $arguments): void
    {
        $process = new Process(array_merge([$command], $arguments));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setBinary(string $binary): void
    {
        $this->binary = $binary;
    }

    public function getBinary(): string
    {
        return $this->binary;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setConfigFile(string $configFile): void
    {
        if (!file_exists($configFile)) {
            throw new \InvalidArgumentException('Cannot find config file: '.$configFile);
        }

        $this->configFile = $configFile;
    }

    public function getConfigFile(): string
    {
        return $this->configFile;
    }
}
