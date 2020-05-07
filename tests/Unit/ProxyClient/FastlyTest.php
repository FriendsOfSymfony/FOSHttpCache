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

use FOS\HttpCache\ProxyClient\Fastly;
use FOS\HttpCache\ProxyClient\HttpDispatcher;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class FastlyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HttpDispatcher|MockInterface
     */
    private $httpDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);
    }

    protected function tearDown(): void
    {
        unset($this->httpDispatcher);
        parent::tearDown();
    }

    protected function getProxyClient(array $options = [])
    {
        $options = [
            'authentication_token' => 'o43r8j34hr',
            'service_identifier' => 'greenpeace',
        ] + $options;

        return new Fastly($this->httpDispatcher, $options);
    }

    public function testInvalidateTagsDefaultSoftPurge()
    {
        $fastly = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('POST', $request->getMethod());

                    $this->assertEquals('o43r8j34hr', $request->getHeaderLine('Fastly-Key'));
                    $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
                    $this->assertEquals('/service/greenpeace/purge', $request->getRequestTarget());

                    $this->assertEquals('1', $request->getHeaderLine('Fastly-Soft-Purge'));
                    $this->assertEquals('', $request->getHeaderLine('Surrogate-Key'));

                    $this->assertEquals('{"surrogate_keys":["post-1","post,type-3"]}', $request->getBody()->getContents());

                    return true;
                }
            ),
            false
        );

        $fastly->invalidateTags(['post-1', 'post,type-3']);
    }

    public function testInvalidateTagsHardPurge()
    {
        $fastly = $this->getProxyClient(['soft_purge' => false]);

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('POST', $request->getMethod());

                    $this->assertEquals('', $request->getHeaderLine('Fastly-Soft-Purge'));
                    $this->assertEquals('', $request->getHeaderLine('Surrogate-Key'));

                    $this->assertEquals('{"surrogate_keys":["post-1","post,type-3"]}', $request->getBody()->getContents());

                    return true;
                }
            ),
            false
        );

        $fastly->invalidateTags(['post-1', 'post,type-3']);
    }

    public function testInvalidateTagsHeadersSplit()
    {
        $fastly = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->twice();

        $tags = [];
        for ($i = 1; $i < 258; ++$i) {
            $tags[] = 'post-'.$i;
        }
        $fastly->invalidateTags($tags);
    }

    public function testPurge()
    {
        $fastly = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('PURGE', $request->getMethod());

                    $this->assertEquals('o43r8j34hr', $request->getHeaderLine('Fastly-Key'));
                    $this->assertEquals('', $request->getHeaderLine('Accept'));
                    $this->assertEquals('/url', $request->getRequestTarget());

                    $this->assertEquals('bar', $request->getHeaderLine('X-Foo'));

                    $this->assertEquals('1', $request->getHeaderLine('Fastly-Soft-Purge'));
                    $this->assertEquals('', $request->getHeaderLine('Surrogate-Key'));

                    return true;
                }
            ),
            false
        );

        $fastly->purge('/url', ['X-Foo' => 'bar']);
    }

    public function testRefresh()
    {
        $fastly = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->twice()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertContains($request->getMethod(), ['PURGE', 'HEAD']);
                    $this->assertEquals('o43r8j34hr', $request->getHeaderLine('Fastly-Key'));
                    $this->assertEquals('/fresh', $request->getRequestTarget());

                    return true;
                }
            ),
            false
        );

        $fastly->refresh('/fresh');
    }

    public function testClear()
    {
        $fastly = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('POST', $request->getMethod());

                    $this->assertEquals('o43r8j34hr', $request->getHeaderLine('Fastly-Key'));
                    $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
                    $this->assertEquals('/service/greenpeace/purge_all', $request->getRequestTarget());

                    $this->assertEquals('', $request->getHeaderLine('Fastly-Soft-Purge'));
                    $this->assertEquals('', $request->getHeaderLine('Surrogate-Key'));

                    return true;
                }
            ),
            false
        );

        $fastly->clear();
    }
}
