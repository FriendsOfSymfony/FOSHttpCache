<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\ProxyClient;

use FOS\HttpCache\ProxyClient\HttpDispatcher;
use FOS\HttpCache\ProxyClient\Varnish;

/**
 * Testing the base methods of the proxy client, using the Varnish client as concrete class.
 */
class HttpProxyClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpDispatcher;

    protected function setUp()
    {
        $this->httpDispatcher = $this
            ->getMockBuilder(HttpDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testFlush()
    {
        $this->httpDispatcher
            ->expects($this->once())
            ->method('flush')
            ->will($this->returnValue(42));

        $varnish = new Varnish($this->httpDispatcher);

        $this->assertEquals(42, $varnish->flush());
    }
}
