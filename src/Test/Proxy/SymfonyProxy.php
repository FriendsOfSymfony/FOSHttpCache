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
 * Controls the Symfony HttpCache proxy server.
 *
 * SYMFONY_CACHE_DIR directory to use for cache
 *                   (default sys_get_temp_dir() + '/foshttpcache-symfony')
 */
class SymfonyProxy implements ProxyInterface
{
    /**
     * Get Symfony cache directory
     *
     * @return string
     */
    public function getCacheDir()
    {
        return defined('SYMFONY_CACHE_DIR') ? SYMFONY_CACHE_DIR : sys_get_temp_dir() . '/foshttpcache-symfony';
    }

    /**
     * Start the proxy server
     */
    public function start()
    {
        $this->clear();
    }

    /**
     * Stop the proxy server
     */
    public function stop()
    {
        // nothing to do
    }

    /**
     * Clear all cached content from the proxy server
     */
    public function clear()
    {
        if (is_dir($this->getCacheDir())) {
            $path = realpath($this->getCacheDir());
            if (!$this->getCacheDir() || '/' == $path) {
                throw new \Exception('Invalid test setup, the cache dir is ' . $path);
            }
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                system('DEL /S '.$path);
            } else {
                system('rm -r '.$path);
            }
        }
    }
}
