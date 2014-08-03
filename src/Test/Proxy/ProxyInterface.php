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

/**
 * Controls a HTTP caching proxy server
 */
interface ProxyInterface
{
    /**
     * Start the proxy server
     */
    public function start();

    /**
     * Stop the proxy server
     */
    public function stop();

    /**
     * Clear all cached content from the proxy server
     */
    public function clear();
}
