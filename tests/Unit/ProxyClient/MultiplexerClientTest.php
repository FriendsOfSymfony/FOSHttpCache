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

use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use FOS\HttpCache\ProxyClient\MultiplexerClient;
use FOS\HttpCache\ProxyClient\ProxyClient;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MultiplexerClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function testInvalidateTags()
    {
        $tags = ['tag-1', 'tag-2'];

        $mockClient = \Mockery::mock(TagCapable::class)
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($tags)
            ->getMock();

        $multiplexer = new MultiplexerClient([$mockClient]);

        $this->assertSame($multiplexer, $multiplexer->invalidateTags($tags));
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
            ->once()
            ->with($url, $headers)
            ->getMock();
        $mockClient3 = \Mockery::mock(ProxyClient::class);

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2, $mockClient3]);

        $this->assertSame($multiplexer, $multiplexer->purge($url, $headers));
    }

    public function testClear()
    {
        $mockClient1 = \Mockery::mock(ClearCapable::class)
            ->shouldReceive('clear')
            ->once()
            ->getMock();
        $mockClient2 = \Mockery::mock(ClearCapable::class)
            ->shouldReceive('clear')
            ->once()
            ->getMock();
        $mockClient3 = \Mockery::mock(ProxyClient::class);

        $multiplexer = new MultiplexerClient([$mockClient1, $mockClient2, $mockClient3]);

        $this->assertSame($multiplexer, $multiplexer->clear());
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
     */
    public function testInvalidClientTest(array $clients)
    {
        $this->expectException(InvalidArgumentException::class);
        new MultiplexerClient($clients);
    }
}
