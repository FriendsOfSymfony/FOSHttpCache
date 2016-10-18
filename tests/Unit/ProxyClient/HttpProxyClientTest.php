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
use Mockery\MockInterface;

/**
 * Testing the base methods of the proxy client, using the Varnish client as concrete class.
 */
class HttpProxyClientTest extends \PHPUnit_Framework_TestCase
{
    public function testFlush()
    {
        /** @var HttpDispatcher|MockInterface $httpDispatcher */
        $httpDispatcher = \Mockery::mock(HttpDispatcher::class)
            ->shouldReceive('flush')
            ->once()
            ->andReturn(42)
            ->getMock();

        $varnish = new Varnish($httpDispatcher);

        $this->assertEquals(42, $varnish->flush());
    }
}
