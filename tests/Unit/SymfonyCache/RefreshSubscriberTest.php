<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\SymfonyCache;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\RefreshSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

class RefreshSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    public function setUp()
    {
        $this->kernel = $this->getMock('FOS\HttpCache\Tests\Unit\SymfonyCache\TestHttpCache');
    }

    public function testRefreshAllowed()
    {
        $request = Request::create('http://example.com/foo');
        $request->headers->addCacheControlDirective('no-cache');
        $response = new Response('Test response');
        $event = new CacheEvent($this->kernel, $request);

        $this->kernel->expects($this->once())
            ->method('fetch')
            ->with($request)
            ->will($this->returnValue($response))
        ;

        $refreshSubscriber = new RefreshSubscriber();
        $refreshSubscriber->handleRefresh($event);

        $this->assertSame($response, $event->getResponse());
    }

    public function testRefreshForbiddenMatcher()
    {
        $this->kernel->expects($this->never())
            ->method('fetch')
        ;

        $matcher = new RequestMatcher('/forbidden');
        $refreshSubscriber = new RefreshSubscriber(array('refresh_client_matcher' => $matcher));
        $request = Request::create('http://example.com/foo');
        $request->headers->addCacheControlDirective('no-cache');
        $event = new CacheEvent($this->kernel, $request);

        $refreshSubscriber->handleRefresh($event);

        $this->assertNull($event->getResponse());
    }

    public function testRefreshForbiddenIp()
    {
        $this->kernel->expects($this->never())
            ->method('fetch')
        ;

        $refreshSubscriber = new RefreshSubscriber(array('refresh_client_ips' => '1.2.3.4'));
        $request = Request::create('http://example.com/foo');
        $request->headers->addCacheControlDirective('no-cache');
        $event = new CacheEvent($this->kernel, $request);

        $refreshSubscriber->handleRefresh($event);
        $this->assertNull($event->getResponse());
    }

    /**
     * Configuring the method to something else should make this subscriber skip the request.
     */
    public function testUnsafe()
    {
        $this->kernel->expects($this->never())
            ->method('fetch')
        ;

        $refreshSubscriber = new RefreshSubscriber();
        $request = Request::create('http://example.com/foo', 'POST');
        $request->headers->addCacheControlDirective('no-cache');
        $event = new CacheEvent($this->kernel, $request);

        $refreshSubscriber->handleRefresh($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * Refresh only happens if no-cache is sent.
     */
    public function testNoRefresh()
    {
        $this->kernel->expects($this->never())
            ->method('fetch')
        ;

        $refreshSubscriber = new RefreshSubscriber();
        $request = Request::create('http://example.com/foo');
        $event = new CacheEvent($this->kernel, $request);

        $refreshSubscriber->handleRefresh($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage does not exist
     */
    public function testInvalidConfiguration()
    {
        new RefreshSubscriber(array('purge_client_ip' => '1.2.3.4'));
    }
}

class TestHttpCache extends HttpCache
{
    public function __construct()
    {}

    /**
     * Made public to allow event subscribers to do refresh operations.
     *
     * {@inheritDoc}
     */
    public function fetch(Request $request, $catch = false)
    {
        return parent::fetch($request, $catch);
    }
}
