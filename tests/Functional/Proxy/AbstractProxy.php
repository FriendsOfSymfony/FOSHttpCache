<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\Proxy;

abstract class AbstractProxy implements ProxyInterface
{
    /**
     * Wait for caching proxy to be started up and reachable
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
                return true == @fsockopen($ip, $port);
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
     * Wait for caching proxy to be started up and reachable
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
                // This doesn't seem to work
                return false == @fsockopen($ip, $port);
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
        for ($i = 0; $i < $timeout; $i++) {
            if ($callback()) {
                return true;
            }

            usleep(1000);
        }

        return false;
    }

}
