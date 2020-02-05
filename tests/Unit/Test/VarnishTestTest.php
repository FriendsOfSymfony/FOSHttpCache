<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Test;

use FOS\HttpCache\Test\Proxy\VarnishProxy;
use FOS\HttpCache\Test\VarnishTest;
use PHPUnit\Framework\TestCase;

class VarnishTestTest extends TestCase
{
    use VarnishTest;

    protected function setUp(): void
    {
        // do not try to set up proxy
    }

    protected function getBinary()
    {
        return '/test/binary';
    }

    protected function getCachingProxyPort()
    {
        return 123;
    }

    protected function getVarnishMgmtPort()
    {
        return 321;
    }

    protected function getCacheDir()
    {
        return '/tmp/foobar';
    }

    public function testGetProxy()
    {
        $proxy = $this->getProxy();
        $this->assertInstanceOf(VarnishProxy::class, $proxy);

        $this->assertEquals('/test/binary', $proxy->getBinary());
        $this->assertEquals(123, $proxy->getPort());
        $this->assertEquals(321, $proxy->getManagementPort());
        $this->assertEquals('/tmp/foobar', $proxy->getCacheDir());
    }
}
