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

use FOS\HttpCache\ProxyClient\Http\HttpAdapter;
use FOS\HttpCache\ProxyClient\Varnish;
use Http\Mock\Client;

/**
 * Testing the base methods of the proxy client, using the Varnish client as concrete class.
 */
class HttpProxyClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock HttpAdapter.
     *
     * @var HttpAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpAdapter;

    protected function setUp()
    {
        $this->httpAdapter = $this
            ->getMockBuilder(HttpAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testFlush()
    {
        $this->httpAdapter
            ->expects($this->once())
            ->method('flush')
            ->will($this->returnValue(1));

        $varnish = new Varnish($this->httpAdapter);

        $this->assertEquals(1, $varnish->flush());
    }
}
