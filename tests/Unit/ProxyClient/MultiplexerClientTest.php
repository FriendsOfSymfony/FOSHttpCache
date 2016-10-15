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

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\MultiplexerClient;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;

class MultiplexerClientTest extends \PHPUnit_Framework_TestCase
{
    public function testBan()
    {
        $headers = ['Header1' => 'Header1-Value'];

        $mockClient1 = $this->getMock(BanInterface::class);
        $mockClient1
            ->expects($this->once())
            ->method('ban')
            ->with($headers)
        ;
        $mockClient2 = $this->getMock(BanInterface::class);
        $mockClient2
            ->expects($this->once())
            ->method('ban')
            ->with($headers)
        ;

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2]);

        $this->assertSame($multiplexer, $multiplexer->ban($headers));
    }

    public function testBanPath()
    {
        $path = 'path/to/ban';
        $contentType = 'text/css';
        $hosts = 'example.com';

        $mockClient1 = $this->getMock(BanInterface::class);
        $mockClient1
            ->expects($this->once())
            ->method('banPath')
            ->with($path, $contentType, $hosts)
        ;
        $mockClient2 = $this->getMock(BanInterface::class);
        $mockClient2
            ->expects($this->once())
            ->method('banPath')
            ->with($path, $contentType, $hosts)
        ;

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2]);

        $this->assertSame($multiplexer, $multiplexer->banPath($path, $contentType, $hosts));
    }

    public function testFlush()
    {
        $mockClient1 = $this->getMock(ProxyClientInterface::class);
        $mockClient1
            ->expects($this->once())
            ->method('flush')
            ->willReturn(4)
        ;
        $mockClient2 = $this->getMock(ProxyClientInterface::class);
        $mockClient2
            ->expects($this->once())
            ->method('flush')
            ->willReturn(6)
        ;

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2]);

        $this->assertEquals(10, $multiplexer->flush());
    }

    public function testRefresh()
    {
        $url = 'example.com';
        $headers = ['Header1' => 'Header1-Value'];

        $mockClient1 = $this->getMock(RefreshInterface::class);
        $mockClient1
            ->expects($this->once())
            ->method('refresh')
            ->with($url, $headers);
        $mockClient2 = $this->getMock(RefreshInterface::class);
        $mockClient2
            ->expects($this->once())
            ->method('refresh')
            ->with($url, $headers);
        $mockClient3 = $this->getMock(ProxyClientInterface::class);

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2, $mockClient3]);

        $this->assertSame($multiplexer, $multiplexer->refresh($url, $headers));
    }

    public function testPurge()
    {
        $url = 'example.com';
        $headers = ['Header1' => 'Header1-Value'];

        $mockClient1 = $this->getMock(PurgeInterface::class);
        $mockClient1
            ->expects($this->once())
            ->method('purge')
            ->with($url, $headers);
        $mockClient2 = $this->getMock(PurgeInterface::class);
        $mockClient2
            ->expects($this->once())
            ->method('purge')
            ->with($url, $headers);
        $mockClient3 = $this->getMock(ProxyClientInterface::class);

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2, $mockClient3]);

        $this->assertSame($multiplexer, $multiplexer->purge($url, $headers));
    }

    public function provideInvalidClient()
    {
        return [
            [['this-is-not-an-object']],
            [[$this]],
        ];
    }

    /**
     * @param array $clients
     *
     * @dataProvider provideInvalidClient
     * @expectedException \FOS\HttpCache\Exception\InvalidArgumentException
     */
    public function testInvalidClientTest(array $clients)
    {
        new MultiplexerClient($clients);
    }
}
