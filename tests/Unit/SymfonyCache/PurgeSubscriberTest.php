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
use FOS\HttpCache\SymfonyCache\PurgeSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

class PurgeSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    public function setUp()
    {
        $this->kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\HttpCache\HttpCache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testPurgeAllowed()
    {
        $store = $this->getMock('Symfony\Component\HttpKernel\HttpCache\StoreInterface');
        $store->expects($this->once())
            ->method('purge')
            ->with('http://example.com/foo')
            ->will($this->returnValue(true))
        ;
        $this->kernel->expects($this->once())
            ->method('getStore')
            ->with()
            ->will($this->returnValue($store))
        ;

        $purgeSubscriber = new PurgeSubscriber();
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($this->kernel, $request);

        $purgeSubscriber->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPurgeAllowedMiss()
    {
        $store = $this->getMock('Symfony\Component\HttpKernel\HttpCache\StoreInterface');
        $store->expects($this->once())
            ->method('purge')
            ->with('http://example.com/foo')
            ->will($this->returnValue(false))
        ;
        $this->kernel->expects($this->once())
            ->method('getStore')
            ->with()
            ->will($this->returnValue($store))
        ;

        $purgeSubscriber = new PurgeSubscriber();
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($this->kernel, $request);

        $purgeSubscriber->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPurgeForbiddenMatcher()
    {
        $this->kernel->expects($this->never())
            ->method('getStore')
        ;

        $matcher = new RequestMatcher('/forbidden');
        $purgeSubscriber = new PurgeSubscriber(array('purge_client_matcher' => $matcher));
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($this->kernel, $request);

        $purgeSubscriber->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testPurgeForbiddenIp()
    {
        $this->kernel->expects($this->never())
            ->method('getStore')
        ;

        $purgeSubscriber = new PurgeSubscriber(array('purge_client_ips' => '1.2.3.4'));
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($this->kernel, $request);

        $purgeSubscriber->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * Configuring the method to something else should make this subscriber skip the request.
     */
    public function testOtherMethod()
    {
        $this->kernel->expects($this->never())
            ->method('getStore')
        ;
        $matcher = $this->getMock('Symfony\Component\HttpFoundation\RequestMatcher');
        $matcher->expects($this->never())
            ->method('isRequestAllowed')
        ;

        $purgeSubscriber = new PurgeSubscriber(array(
            'purge_client_matcher' => $matcher,
            'purge_method' => 'FOO',
        ));
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($this->kernel, $request);

        $purgeSubscriber->handlePurge($event);
        $this->assertNull($event->getResponse());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage does not exist
     */
    public function testInvalidConfiguration()
    {
        new PurgeSubscriber(array('purge_client_ip' => '1.2.3.4'));
    }
}
