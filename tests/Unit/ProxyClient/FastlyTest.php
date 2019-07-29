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
use FOS\HttpCache\ProxyClient\Fastly;
use FOS\HttpCache\ProxyClient\Symfony;
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

    protected function setUp()
    {
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);
    }

    protected function getProxyClient(array $options = [])
    {
        $options = [
            'authentication_token' => 'o43r8j34hr',
            'service_identifier' => 'greenpeace',
        ] + $options;

        return new Fastly($this->httpDispatcher, $options);
    }

    public function testInvalidateTags()
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
                    $this->assertEquals('post-1 post,type-3', $request->getHeaderLine('Surrogate-Key'));


                    return true;
                }
            ), false
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
                    $this->assertEquals('post-1 post,type-3', $request->getHeaderLine('Surrogate-Key'));

                    return true;
                }
            ), false
        );

        $fastly->invalidateTags(['post-1', 'post,type-3']);
    }

    public function testInvalidateTags_HeadersSplit()
    {
        $fastly = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->twice();

        $tags = [];
        for ($i = 1; $i < 258; $i++) {
            $tags[] = 'post-' . $i;
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
            ), false
        );

        $fastly->purge('/url', ['X-Foo' => 'bar']);
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
