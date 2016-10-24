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

use FOS\HttpCache\Test\Proxy\NginxProxy;
use FOS\HttpCache\Test\NginxTest;

class NginxTestTest extends \PHPUnit_Framework_TestCase
{
    use NginxTest;

    protected function setUp()
    {
        // do not try to set up proxy
    }

    protected function getBinary()
    {
        return '/test/binary';
    }

    protected function getCacheDir()
    {
        return '/tmp/foobar';
    }

    public function testGetProxy()
    {
        $proxy = $this->getProxy();
        $this->assertInstanceOf(NginxProxy::class, $proxy);

        $this->assertEquals('/test/binary', $proxy->getBinary());
        $this->assertEquals('/tmp/foobar', $proxy->getCacheDir());
    }
}
