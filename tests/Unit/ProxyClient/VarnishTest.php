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
use FOS\HttpCache\ProxyClient\HttpDispatcher;
use FOS\HttpCache\ProxyClient\Varnish;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class VarnishTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HttpDispatcher|MockInterface
     */
    private $httpDispatcher;

    protected function setUp(): void
    {
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);
    }

    public function testBanHeaders()
    {
        $options = [
            'default_ban_headers' => [
                'A' => 'B',
                'Test' => '.*',
            ],
        ];

        $varnish = new Varnish($this->httpDispatcher, $options);
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('BAN', $request->getMethod());
                    $this->assertEquals('.*', $request->getHeaderLine('X-Host'));
                    $this->assertEquals('.*', $request->getHeaderLine('X-Url'));
                    $this->assertEquals('.*', $request->getHeaderLine('X-Content-Type'));

                    $this->assertEquals('Toast', $request->getHeaderLine('Test'));
                    $this->assertEquals('value', $request->getHeaderLine('additional'));
                    $this->assertEquals('B', $request->getHeaderLine('A'));

                    return true;
                }
            ),
            false
        );

        $varnish->ban([
            'Test' => 'Toast',
            'additional' => 'value',
        ]);
    }

    public function testBanPath()
    {
        $varnish = new Varnish($this->httpDispatcher);
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('BAN', $request->getMethod());
                    $this->assertEquals('^(fos.lo|fos2.lo)$', $request->getHeaderLine('X-Host'));
                    $this->assertEquals('/articles/.*', $request->getHeaderLine('X-Url'));
                    $this->assertEquals('text/html', $request->getHeaderLine('X-Content-Type'));

                    return true;
                }
            ),
            false
        );
        $hosts = ['fos.lo', 'fos2.lo'];
        $varnish->banPath('/articles/.*', 'text/html', $hosts);
    }

    public function testPurgekeys()
    {
        $options = [
            'tag_mode' => 'purgekeys',
        ];

        $varnish = new Varnish($this->httpDispatcher, $options);
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('PURGEKEYS', $request->getMethod());
                    $this->assertEquals('post-1 post,type-3', $request->getHeaderLine('xkey-softpurge'));

                    return true;
                }
            ),
            false
        );

        $varnish->invalidateTags(['post-1', 'post,type-3']);
    }

    public function testHardPurgekeys()
    {
        $options = [
            'tag_mode' => 'purgekeys',
            'tags_header' => 'xkey-purge',
        ];

        $varnish = new Varnish($this->httpDispatcher, $options);
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('PURGEKEYS', $request->getMethod());
                    $this->assertEquals('post-1 post,type-3', $request->getHeaderLine('xkey-purge'));

                    return true;
                }
            ),
            false
        );

        $varnish->invalidateTags(['post-1', 'post,type-3']);
    }

    public function testBanPathEmptyHost()
    {
        $varnish = new Varnish($this->httpDispatcher);

        $hosts = [];
        $this->expectException(InvalidArgumentException::class);
        $varnish->banPath('/articles/.*', 'text/html', $hosts);
    }

    public function testTagsHeaders()
    {
        $options = [
            'default_ban_headers' => [
                'A' => 'B',
                'Test' => '.*',
            ],
        ];

        $varnish = new Varnish($this->httpDispatcher, $options);
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('BAN', $request->getMethod());
                    $this->assertEquals('(^|,)(mytag|othertag)(,|$)', $request->getHeaderLine('X-Cache-Tags'));

                    // That default BANs is taken into account also for tags as they are powered by BAN in this client.
                    $this->assertEquals('.*', $request->getHeaderLine('Test'));
                    $this->assertEquals('B', $request->getHeaderLine('A'));

                    return true;
                }
            ),
            false
        );

        $varnish->invalidateTags(['mytag', 'othertag']);
    }

    public function testTagsHeadersEscapingAndCustomHeader()
    {
        $options = [
            'tags_header' => 'X-Tags-TRex',
        ];

        $varnish = new Varnish($this->httpDispatcher, $options);
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('BAN', $request->getMethod());
                    $this->assertEquals('(^|,)(post\-1|post_type\-3)(,|$)', $request->getHeaderLine('X-Tags-TRex'));

                    return true;
                }
            ),
            false
        );

        $varnish->invalidateTags(['post-1', 'post,type-3']);
    }

    public function testTagsHeadersSplit()
    {
        $varnish = new Varnish($this->httpDispatcher, ['header_length' => 7]);
        $this->httpDispatcher->shouldReceive('invalidate')->twice();

        $varnish->invalidateTags(['post-1', 'post-2']);
    }

    public function testPurge()
    {
        $varnish = new Varnish($this->httpDispatcher);

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('PURGE', $request->getMethod());

                    $this->assertEquals('/url', $request->getUri());
                    $this->assertEquals('bar', $request->getHeaderLine('X-Foo'));

                    return true;
                }
            ),
            true
        );

        $varnish->purge('/url', ['X-Foo' => 'bar']);
    }

    public function testRefresh()
    {
        $varnish = new Varnish($this->httpDispatcher);
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('GET', $request->getMethod());
                    $this->assertEquals('/fresh', $request->getUri());
                    $this->assertStringContainsString('no-cache', $request->getHeaderLine('Cache-Control'));

                    return true;
                }
            ),
            true
        );

        $varnish->refresh('/fresh');
    }
}
