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

class LiteSpeedProxy extends AbstractProxy
{
    protected $binary = '/usr/local/lsws/bin/lswsctrl';

    protected $port = 8080;

    protected $cacheDir = '/usr/local/lsws/cachedata';

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $process = $this->runCommand([
            $this->getBinary(),
            'status',
        ], true);

        // Already running, restart
        if (false !== strpos($process->getOutput(), 'litespeed is running with PID')) {
            $this->runCommand([
                $this->getBinary(),
                'restart',
            ], true);

            return;
        }

        // Otherwise start
        $this->runCommand([
            $this->getBinary(),
            'start',
        ], true);

        $this->waitFor($this->getIp(), $this->getPort(), 2000);
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->runCommand([
            $this->getBinary(),
            'stop',
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        // Runs as sudo to make sure it can be removed
        $this->runCommand([
            'rm',
            '-rf',
            $this->getCacheDir(),
        ], true);

        // Does not run as sudo to make sure it's created using the correct user
        $this->runCommand([
            'mkdir',
            '-p',
            $this->getCacheDir(),
        ]);

        $this->start();
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }
}
