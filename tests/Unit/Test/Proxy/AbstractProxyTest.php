<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Test\Proxy;

use FOS\HttpCache\Test\Proxy\AbstractProxy;
use PHPUnit\Framework\TestCase;

class AbstractProxyTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Caching proxy still up at
     */
    public function testWaitTimeout()
    {
        $proxy = new ProxyPartial();
        $proxy->start();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage /path/to/not/exists
     */
    public function testRunFailure()
    {
        $proxy = new ProxyPartial();
        $proxy->run();
    }
}

class ProxyPartial extends AbstractProxy
{
    public function start()
    {
        $this->waitUntil('localhost', 6666, 0);
    }

    public function stop()
    {
    }

    public function clear()
    {
    }

    public function run()
    {
        $this->runCommand('/path/to/not/exists', []);
    }
}
