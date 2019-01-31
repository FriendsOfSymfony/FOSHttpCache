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
    protected $binary = 'lswsctrl';

    protected $port = 80;

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
        $this->runCommand(
            $this->getBinary(),
            [
                'restart',
            ]
        );
    }
}
