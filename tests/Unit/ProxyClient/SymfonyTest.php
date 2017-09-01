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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class SymfonyTest extends TestCase
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

    public function testPurge()
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
            ), true
        );

        $symfony->purge('/url', ['X-Foo' => 'bar']);
    }

    public function testInvalidateTags()
    {
        $symfony = new Symfony($this->httpDispatcher);

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('PURGETAGS', $request->getMethod());

                    $this->assertEquals('/', $request->getUri());
                    $this->assertContains('foobar,other tag', $request->getHeaderLine('X-Cache-Tags'));

                    return true;
                }
            ),
            true
        );

        $symfony->invalidateTags(['foobar', 'other tag']);
    }

    public function testInvalidateTagsWithALotOfTags()
    {
        $dispatcher = $this->createMock(HttpDispatcher::class);
        $dispatcher
            ->expects($this->exactly(3))
            ->method('invalidate')
            ->withConsecutive(
                [
                    $this->callback(function (RequestInterface $request) {
                        $this->assertEquals('PURGETAGS', $request->getMethod());
                        $this->assertContains('foobar,foobar1,foobar2,foobar3,foobar4,foobar5,foobar6,foobar7,foobar8,foobar9,foobar10,foobar11', $request->getHeaderLine('X-Cache-Tags'));

                        return true;
                    }),
                    true,
                ],
                [
                    $this->callback(function (RequestInterface $request) {
                        $this->assertEquals('PURGETAGS', $request->getMethod());
                        $this->assertContains('foobar12,foobar13,foobar14,foobar15,foobar16,foobar17,foobar18,foobar19,foobar20,foobar22,foobar23', $request->getHeaderLine('X-Cache-Tags'));

                        return true;
                    }),
                    true,
                ],
                [
                    $this->callback(function (RequestInterface $request) {
                        $this->assertEquals('PURGETAGS', $request->getMethod());
                        $this->assertContains('foobar24,foobar25', $request->getHeaderLine('X-Cache-Tags'));

                        return true;
                    }),
                    true,
                ]
            );

        $symfony = new Symfony($dispatcher, ['purge_tags_header_length' => 100]);

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
            'foobar22',
            'foobar23',
            'foobar24',
            'foobar25',
        ]);
    }

    public function testRefresh()
    {
        $symfony = new Symfony($this->httpDispatcher);

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('GET', $request->getMethod());

                    $this->assertEquals('/fresh', $request->getUri());
                    $this->assertContains('no-cache', $request->getHeaderLine('Cache-Control'));

                    return true;
                }
            ),
            true
        );

        $symfony->refresh('/fresh');
    }
}
