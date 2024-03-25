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
    public function getCacheDir(): string
    {
        $path = defined('SYMFONY_CACHE_DIR') ? SYMFONY_CACHE_DIR : sys_get_temp_dir().'/foshttpcache-symfony';
        if (!$path || '/' === $path) {
            throw new \RuntimeException('Invalid test setup, the cache dir is '.$path);
        }

        return $path;
    }

    public function start(): void
    {
        $this->clear();
    }

    public function stop(): void
    {
        // nothing to do
    }

    public function clear(): void
    {
        $path = realpath($this->getCacheDir());

        // false means the directory does not exist yet - it surely is empty then
        if (!is_dir($path)) {
            return;
        }

        $path = $this->getCacheDir();
        if (0 === stripos(PHP_OS_FAMILY, 'WIN')) {
            // @codeCoverageIgnoreStart
            system('DEL /S '.$path);
        } else {
            // @codeCoverageIgnoreEnd
            system('rm -r '.$path);
        }
    }
}
