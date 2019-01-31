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

class LiteSpeedProxy extends AbstractProxy
{
    protected $binary = '/usr/local/lsws/bin/lswsctrl';

    protected $port = 8080;

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $this->runCommand(
            $this->getBinary(),
            [
                'start',
            ]
        );

        $this->waitFor($this->getIp(), $this->getPort(), 2000);
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->runCommand(
            $this->getBinary(),
            [
                'stop',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        try {
            $this->runCommand(
                $this->getBinary(),
                [
                    'status',
                ]
            );

            $this->stop();

        } catch (ProcessFailedException $e) {
            // Not running, no need to stop
        }

        $this->start();
    }
}
