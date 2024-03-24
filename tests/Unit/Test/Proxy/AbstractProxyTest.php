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
    public function testWaitTimeout(): void
    {
        $proxy = new ProxyPartial();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Caching proxy still up at');
        $proxy->start();
    }

    public function testRunFailure(): void
    {
        $proxy = new ProxyPartial();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('/path/to/not/exists');
        $proxy->run();
    }
}

class ProxyPartial extends AbstractProxy
{
    public function start(): void
    {
        $this->waitUntil('localhost', 6666, 0);
    }

    public function stop(): void
    {
    }

    public function clear(): void
    {
    }

    public function run(): void
    {
        $this->runCommand('/path/to/not/exists', []);
    }
}
