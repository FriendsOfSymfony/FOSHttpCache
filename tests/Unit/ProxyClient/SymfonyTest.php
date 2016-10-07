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
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;

class SymfonyTest extends \PHPUnit_Framework_TestCase
{
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
            )
        );

        $symfony->purge('/url', ['X-Foo' => 'bar']);
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
            )
        );

        $symfony->refresh('/fresh');
    }
}
