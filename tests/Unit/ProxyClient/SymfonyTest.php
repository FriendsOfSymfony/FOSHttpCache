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
use FOS\HttpCache\ProxyClient\Symfony;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class SymfonyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private HttpDispatcher&MockInterface $httpDispatcher;

    protected function setUp(): void
    {
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);
    }

    public function testPurge(): void
    {
        $symfony = new Symfony($this->httpDispatcher);

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

        $symfony->purge('/url', ['X-Foo' => 'bar']);
    }

    public function testInvalidateTags(): void
    {
        $symfony = new Symfony($this->httpDispatcher);

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('PURGETAGS', $request->getMethod());

                    $this->assertEquals('/', $request->getUri());
                    $this->assertStringContainsString('foobar,other tag', $request->getHeaderLine('X-Cache-Tags'));

                    return true;
                }
            ),
            false
        );

        $symfony->invalidateTags(['foobar', 'other tag']);
    }

    public function testInvalidateTagsWithALotOfTags(): void
    {
        $tags = [
            'foobar,foobar1,foobar2,foobar3,foobar4,foobar5,foobar6,foobar7,foobar8,foobar9,foobar10' => true,
            'foobar11,foobar12,foobar13,foobar14,foobar15,foobar16,foobar17,foobar18,foobar19,foobar20,foobar21' => true,
            'foobar22,foobar23,foobar24,foobar25' => true,
        ];

        /** @var HttpDispatcher&MockObject $dispatcher */
        $dispatcher = $this->createMock(HttpDispatcher::class);
        $dispatcher
            ->expects($this->exactly(3))
            ->method('invalidate')
            ->with($this->callback(function (RequestInterface $request) use (&$tags): bool {
                $this->assertSame('PURGETAGS', $request->getMethod());
                $purgeTags = $request->getHeaderLine('X-Cache-Tags');
                if (!array_key_exists($purgeTags, $tags)) {
                    $this->fail("Unexpected request to purge $purgeTags");
                }
                unset($tags[$purgeTags]);

                return true;
            }))
        ;

        $symfony = new Symfony($dispatcher, ['header_length' => 100]);

        $symfony->invalidateTags([
            'foobar',
            'foobar1',
            'foobar2',
            'foobar3',
            'foobar4',
            'foobar5',
            'foobar6',
            'foobar7',
            'foobar8',
            'foobar9',
            'foobar10',
            'foobar11',
            'foobar12',
            'foobar13',
            'foobar14',
            'foobar15',
            'foobar16',
            'foobar17',
            'foobar18',
            'foobar19',
            'foobar20',
            'foobar21',
            'foobar22',
            'foobar23',
            'foobar24',
            'foobar25',
        ]);

        $this->assertCount(0, $tags);
    }

    public function testClear(): void
    {
        $symfony = new Symfony($this->httpDispatcher);

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('PURGE', $request->getMethod());

                    $this->assertEquals('/', $request->getUri());
                    $this->assertEquals('true', $request->getHeaderLine('Clear-Cache'));

                    return true;
                }
            ),
            false
        );

        $symfony->clear();
    }

    public function testRefresh(): void
    {
        $symfony = new Symfony($this->httpDispatcher);

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

        $symfony->refresh('/fresh');
    }
}
