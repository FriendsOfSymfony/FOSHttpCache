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

use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\MultiplexerClient;
use FOS\HttpCache\ProxyClient\ProxyClient;

class MultiplexerClientTest extends \PHPUnit_Framework_TestCase
{
    public function testBan()
    {
        $headers = ['Header1' => 'Header1-Value'];

        $mockClient1 = \Mockery::mock(BanCapable::class)
            ->shouldReceive('ban')
            ->once()
            ->with($headers)
            ->getMock();

        $mockClient2 = \Mockery::mock(BanCapable::class)
            ->shouldReceive('ban')
            ->once()
            ->with($headers)
            ->getMock();

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2]);

        $this->assertSame($multiplexer, $multiplexer->ban($headers));
    }

    public function testBanPath()
    {
        $path = 'path/to/ban';
        $contentType = 'text/css';
        $hosts = 'example.com';

        $mockClient1 = \Mockery::mock(BanCapable::class)
            ->shouldReceive('banPath')
            ->once()
            ->with($path, $contentType, $hosts)
            ->getMock();
        $mockClient2 = \Mockery::mock(BanCapable::class)
            ->shouldReceive('banPath')
            ->once()
            ->with($path, $contentType, $hosts)
            ->getMock();

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2]);

        $this->assertSame($multiplexer, $multiplexer->banPath($path, $contentType, $hosts));
    }

    public function testFlush()
    {
        $mockClient1 = \Mockery::mock(ProxyClient::class)
            ->shouldReceive('flush')
            ->once()
            ->andReturn(4)
            ->getMock();
        $mockClient2 = \Mockery::mock(ProxyClient::class)
            ->shouldReceive('flush')
            ->once()
            ->andReturn(6)
            ->getMock();

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2]);

        $this->assertEquals(10, $multiplexer->flush());
    }

    public function testRefresh()
    {
        $url = 'example.com';
        $headers = ['Header1' => 'Header1-Value'];

        $mockClient1 = \Mockery::mock(RefreshCapable::class)
            ->shouldReceive('refresh')
            ->once()
            ->with($url, $headers)
            ->getMock();
        $mockClient2 = \Mockery::mock(RefreshCapable::class)
            ->shouldReceive('refresh')
            ->once()
            ->with($url, $headers)
            ->getMock();
        $mockClient3 = \Mockery::mock(ProxyClient::class);

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2, $mockClient3]);

        $this->assertSame($multiplexer, $multiplexer->refresh($url, $headers));
    }

    public function testPurge()
    {
        $url = 'example.com';
        $headers = ['Header1' => 'Header1-Value'];

        $mockClient1 = \Mockery::mock(PurgeCapable::class)
            ->shouldReceive('purge')
            ->once()
            ->with($url, $headers)
            ->getMock();
        $mockClient2 = \Mockery::mock(PurgeCapable::class)
            ->shouldReceive('purge')
            ->with($url, $headers)
            ->getMock();
        $mockClient3 = \Mockery::mock(ProxyClient::class);

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
     * @param ProxyClient[] $clients
     *
     * @dataProvider provideInvalidClient
     * @expectedException \FOS\HttpCache\Exception\InvalidArgumentException
     */
    public function testInvalidClientTest(array $clients)
    {
        new MultiplexerClient($clients);
    }
}
