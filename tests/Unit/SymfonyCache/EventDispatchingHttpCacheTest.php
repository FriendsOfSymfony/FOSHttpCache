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

use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class EventDispatchingHttpCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return EventDispatchingHttpCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHttpCachePartialMock(array $mockedMethods = null)
    {
        $mock = $this
            ->getMockBuilder('\FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache')
            ->setMethods( $mockedMethods )
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // Force setting options property since we can't use original constructor.
        $options = array(
            'debug' => false,
            'default_ttl' => 0,
            'private_headers' => array( 'Authorization', 'Cookie' ),
            'allow_reload' => false,
            'allow_revalidate' => false,
            'stale_while_revalidate' => 2,
            'stale_if_error' => 60,
        );

        $refHttpCache = new \ReflectionClass('Symfony\Component\HttpKernel\HttpCache\HttpCache');
        // Workaround for Symfony 2.3 where $options property is not defined.
        if (!$refHttpCache->hasProperty('options')) {
            $mock->options = $options;
        } else {
            $refOptions = $refHttpCache->getProperty('options');
            $refOptions->setAccessible(true);
            $refOptions->setValue($mock, $options );
        }

        return $mock;
    }

    public function testCalledHandle()
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock(array('lookup'));
        $subscriber = new TestSubscriber($this, $httpCache, $request);
        $httpCache->addSubscriber($subscriber);
        $httpCache
            ->expects($this->any())
            ->method('lookup')
            ->with($request)
            ->will($this->returnValue($response))
        ;

        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MASTER_REQUEST, $catch));
        $this->assertEquals(1, $subscriber->handleHits);
    }

    public function testAbortHandle()
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock(array('lookup'));
        $subscriber = new TestSubscriber($this, $httpCache, $request);
        $subscriber->handleResponse = $response;
        $httpCache->addSubscriber($subscriber);
        $httpCache
            ->expects($this->never())
            ->method('lookup')
        ;

        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MASTER_REQUEST, $catch));
        $this->assertEquals(1, $subscriber->handleHits);
    }

    public function testCalledInvalidate()
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response('', 500);

        $httpCache = $this->getHttpCachePartialMock(array('pass'));
        $subscriber = new TestSubscriber($this, $httpCache, $request);
        $httpCache->addSubscriber($subscriber);
        $httpCache
            ->expects($this->any())
            ->method('pass')
            ->with($request)
            ->will($this->returnValue($response))
        ;
        $refHttpCache = new \ReflectionObject($httpCache);
        $method = $refHttpCache->getMethod('invalidate');
        $method->setAccessible(true);

        $this->assertSame($response, $method->invokeArgs($httpCache, array($request, $catch)));
        $this->assertEquals(1, $subscriber->invalidateHits);
    }

    public function testAbortInvalidate()
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response('', 400);

        $httpCache = $this->getHttpCachePartialMock(array('pass'));
        $subscriber = new TestSubscriber($this, $httpCache, $request);
        $subscriber->invalidateResponse = $response;
        $httpCache->addSubscriber($subscriber);
        $httpCache
            ->expects($this->never())
            ->method('pass')
        ;
        $refHttpCache = new \ReflectionObject($httpCache);
        $method = $refHttpCache->getMethod('invalidate');
        $method->setAccessible(true);

        $this->assertSame($response, $method->invokeArgs($httpCache, array($request, $catch)));
        $this->assertEquals(1, $subscriber->invalidateHits);
    }
}

class TestSubscriber implements EventSubscriberInterface
{
    public $handleHits = 0;
    public $invalidateHits = 0;
    public $handleResponse;
    public $invalidateResponse;
    private $test;
    private $kernel;
    private $request;

    public function __construct($test, $kernel, $request)
    {
        $this->test = $test;
        $this->kernel = $kernel;
        $this->request = $request;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_HANDLE => 'preHandle',
            Events::PRE_INVALIDATE => 'preInvalidate'
        );
    }

    public function preHandle(CacheEvent $event)
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        if ($this->handleResponse) {
            $event->setResponse($this->handleResponse);
        }
        $this->handleHits++;
    }

    public function preInvalidate(CacheEvent $event)
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        if ($this->invalidateResponse) {
            $event->setResponse($this->invalidateResponse);
        }
        $this->invalidateHits++;
    }
}
