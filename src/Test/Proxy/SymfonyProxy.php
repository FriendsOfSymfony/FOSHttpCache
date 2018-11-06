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
     * Get Symfony cache directory.
     *
     * @return string
     */
    public function getCacheDir()
    {
        $path = defined('SYMFONY_CACHE_DIR') ? SYMFONY_CACHE_DIR : sys_get_temp_dir().'/foshttpcache-symfony';
        if (!$path || '/' === $path) {
            throw new \RuntimeException('Invalid test setup, the cache dir is '.$path);
        }

        return $path;
    }

    /**
     * Start the proxy server.
     */
    public function start()
    {
        $this->clear();
    }

    /**
     * Stop the proxy server.
     */
    public function stop()
    {
        // nothing to do
    }

    /**
     * Clear all cached content from the proxy server.
     */
    public function clear()
    {
        $path = realpath($this->getCacheDir());

        // false means the directory does not exist yet - it surely is empty then
        if (!is_dir($path)) {
            return;
        }

        $path = $this->getCacheDir();
        if (0 === stripos(PHP_OS, 'WIN')) {
            // @codeCoverageIgnoreStart
            system('DEL /S '.$path);
        } else {
            // @codeCoverageIgnoreEnd
            system('rm -r '.$path);
        }
    }
}
