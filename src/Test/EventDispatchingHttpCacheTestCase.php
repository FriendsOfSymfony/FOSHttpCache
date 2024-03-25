<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\Events;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * This test ensures that the EventDispatchingHttpCache trait is correctly used.
 */
abstract class EventDispatchingHttpCacheTestCase extends TestCase
{
    /**
     * Specify the CacheInvalidationInterface HttpCache class to test.
     *
     * @return class-string Fully qualified class name of the AppCache
     */
    abstract protected function getCacheClass(): string;

    /**
     * Create a partial mock of the HttpCache to only test some methods.
     *
     * @param string[] $mockedMethods List of methods to mock
     */
    protected function getHttpCachePartialMock(array $mockedMethods = []): MockObject&CacheInvalidation
    {
        $mock = $this->createPartialMock($this->getCacheClass(), $mockedMethods);

        $this->assertInstanceOf(CacheInvalidation::class, $mock);

        // Force setting options property since we can't use original constructor.
        $options = [
            'debug' => false,
            'default_ttl' => 0,
            'private_headers' => ['Authorization', 'Cookie'],
            'skip_response_headers' => ['Set-Cookie'],
            'allow_reload' => false,
            'allow_revalidate' => false,
            'stale_while_revalidate' => 2,
            'stale_if_error' => 60,
            'trace_level' => 'full',
            'trace_header' => 'FOSHttpCache',
        ];

        $refHttpCache = new \ReflectionClass(HttpCache::class);
        $refOptions = $refHttpCache->getProperty('options');
        $refOptions->setValue($mock, $options);

        $surrogate = $refHttpCache->getProperty('surrogate');
        $surrogate->setValue($mock, null);

        return $mock;
    }

    /**
     * Set the store property on a HttpCache to a StoreInterface expecting one write with request and response.
     */
    protected function setStoreMock(CacheInvalidation $httpCache, Request $request, Response $response): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store
            ->expects($this->once())
            ->method('write')
            ->with($request, $response)
        ;
        $refHttpCache = new \ReflectionClass(HttpCache::class);
        $refStore = $refHttpCache->getProperty('store');
        $refStore->setValue($httpCache, $store);
    }

    /**
     * Assert that preHandle and postHandle are called.
     */
    public function testHandleCalled(): void
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock(['lookup']);
        $testListener = new TestListener($this, $httpCache, $request);
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache->addSubscriber($testListener);
        $httpCache
            ->method('lookup')
            ->with($request)
            ->willReturn($response)
        ;

        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MAIN_REQUEST, $catch));
        $this->assertEquals(1, $testListener->preHandleCalls);
        $this->assertEquals(1, $testListener->postHandleCalls);
    }

    /**
     * Assert that when preHandle returns a response, that response is used and the normal kernel flow stopped.
     *
     * @depends testHandleCalled
     */
    public function testPreHandleReturnEarly(): void
    {
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock(['lookup']);
        $testListener = new TestListener($this, $httpCache, $request);
        $testListener->preHandleResponse = $response;
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache->addSubscriber($testListener);
        $httpCache
            ->expects($this->never())
            ->method('lookup')
        ;

        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MAIN_REQUEST));
        $this->assertEquals(1, $testListener->preHandleCalls);
        $this->assertEquals(1, $testListener->postHandleCalls);
    }

    /**
     * Assert that postHandle can update the response.
     *
     * @depends testHandleCalled
     */
    public function testPostHandleReturn(): void
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $regularResponse = new Response();
        $postResponse = new Response();

        $httpCache = $this->getHttpCachePartialMock(['lookup']);
        $testListener = new TestListener($this, $httpCache, $request);
        $testListener->postHandleResponse = $postResponse;
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache->addSubscriber($testListener);
        $httpCache
            ->method('lookup')
            ->with($request)
            ->willReturn($regularResponse)
        ;

        $this->assertSame($postResponse, $httpCache->handle($request, HttpKernelInterface::MAIN_REQUEST, $catch));
        $this->assertEquals(1, $testListener->preHandleCalls);
        $this->assertEquals(1, $testListener->postHandleCalls);
    }

    /**
     * Assert that postHandle is called and the response can be updated even when preHandle returned a response.
     *
     * @depends testHandleCalled
     */
    public function testPostHandleAfterPreHandle(): void
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $preResponse = new Response();
        $postResponse = new Response();

        $httpCache = $this->getHttpCachePartialMock(['lookup']);
        $testListener = new TestListener($this, $httpCache, $request);
        $testListener->preHandleResponse = $preResponse;
        $testListener->postHandleResponse = $postResponse;
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache->addSubscriber($testListener);
        $httpCache
            ->expects($this->never())
            ->method('lookup')
        ;

        $this->assertSame($postResponse, $httpCache->handle($request, HttpKernelInterface::MAIN_REQUEST, $catch));
        $this->assertEquals(1, $testListener->preHandleCalls);
        $this->assertEquals(1, $testListener->postHandleCalls);
    }

    /**
     * Assert that preStore is called.
     */
    public function testPreStoreCalled(): void
    {
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock();
        $testListener = new TestListener($this, $httpCache, $request);
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache->addSubscriber($testListener);

        $this->setStoreMock($httpCache, $request, $response);

        $refHttpCache = new \ReflectionObject($httpCache);
        $method = $refHttpCache->getMethod('store');
        $method->invokeArgs($httpCache, [$request, $response]);
        $this->assertEquals(1, $testListener->preStoreCalls);
    }

    /**
     * Assert that preStore response is used when provided.
     */
    public function testPreStoreResponse(): void
    {
        $request = Request::create('/foo', 'GET');
        $regularResponse = new Response();
        $preStoreResponse = new Response();

        $httpCache = $this->getHttpCachePartialMock();
        $testListener = new TestListener($this, $httpCache, $request);
        $testListener->preStoreResponse = $preStoreResponse;
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache->addSubscriber($testListener);

        $this->setStoreMock($httpCache, $request, $preStoreResponse);

        $refHttpCache = new \ReflectionObject($httpCache);
        $method = $refHttpCache->getMethod('store');
        $method->invokeArgs($httpCache, [$request, $regularResponse]);
        $this->assertEquals(1, $testListener->preStoreCalls);
    }

    /**
     * Assert that preInvalidate is called.
     */
    public function testPreInvalidateCalled(): void
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response('', 500);

        $httpCache = $this->getHttpCachePartialMock(['pass']);
        $testListener = new TestListener($this, $httpCache, $request);
        $httpCache->addSubscriber($testListener);
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache
            ->method('pass')
            ->with($request)
            ->willReturn($response)
        ;
        $refHttpCache = new \ReflectionObject($httpCache);
        $method = $refHttpCache->getMethod('invalidate');

        $this->assertSame($response, $method->invokeArgs($httpCache, [$request, $catch]));
        $this->assertEquals(1, $testListener->preInvalidateCalls);
    }

    /**
     * Assert that when preInvalidate returns a response, that response is used and the normal kernel flow stopped.
     *
     * @depends testPreInvalidateCalled
     */
    public function testPreInvalidateReturnEarly(): void
    {
        $catch = true;
        $request = Request::create('/foo', 'GET');
        $response = new Response('', 400);

        $httpCache = $this->getHttpCachePartialMock(['pass']);
        $testListener = new TestListener($this, $httpCache, $request);
        $testListener->preInvalidateResponse = $response;
        $httpCache->addSubscriber($testListener);
        $this->assertTrue(method_exists($httpCache, 'addSubscriber'));
        $httpCache
            ->expects($this->never())
            ->method('pass')
        ;
        $refHttpCache = new \ReflectionObject($httpCache);
        $method = $refHttpCache->getMethod('invalidate');

        $this->assertSame($response, $method->invokeArgs($httpCache, [$request, $catch]));
        $this->assertEquals(1, $testListener->preInvalidateCalls);
    }

    public function testAddListener(): void
    {
        $request = Request::create('/foo', 'GET');
        $response = new Response();

        $httpCache = $this->getHttpCachePartialMock(['lookup']);
        $simpleListener = new SimpleListener($this, $httpCache, $request);
        $httpCache->addListener(Events::PRE_HANDLE, [$simpleListener, 'callback']);

        $httpCache
            ->method('lookup')
            ->with($request)
            ->willReturn($response)
        ;

        $this->assertSame($response, $httpCache->handle($request, HttpKernelInterface::MAIN_REQUEST));
        $this->assertEquals(1, $simpleListener->calls);
    }
}

class TestListener implements EventSubscriberInterface
{
    /**
     * Count how many times preHandle has been called.
     */
    public int $preHandleCalls = 0;

    /**
     * Count how many times postHandle has been called.
     */
    public int $postHandleCalls = 0;

    /**
     * Count how many times preStore has been called.
     */
    public int $preStoreCalls = 0;

    /**
     * Count how many times preInvalidate has been called.
     */
    public int $preInvalidateCalls = 0;

    /**
     * A response to set during the preHandle.
     */
    public ?Response $preHandleResponse = null;

    /**
     * A response to set during the postHandle.
     */
    public ?Response $postHandleResponse = null;

    /**
     * A response to set during the preStore.
     */
    public ?Response $preStoreResponse = null;

    /**
     * A response to set during the preInvalidate.
     */
    public ?Response $preInvalidateResponse = null;

    /**
     * To do assertions.
     */
    private EventDispatchingHttpCacheTestCase $test;

    /**
     * The kernel to ensure the event carries the correct kernel.
     */
    private CacheInvalidation $kernel;

    /**
     * The request to ensure the event carries the correct request.
     */
    private Request $request;

    public function __construct(
        EventDispatchingHttpCacheTestCase $test,
        CacheInvalidation $kernel,
        Request $request
    ) {
        $this->test = $test;
        $this->kernel = $kernel;
        $this->request = $request;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
            Events::POST_HANDLE => 'postHandle',
            Events::PRE_STORE => 'preStore',
            Events::PRE_INVALIDATE => 'preInvalidate',
        ];
    }

    public function preHandle(CacheEvent $event): void
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        if ($this->preHandleResponse) {
            $event->setResponse($this->preHandleResponse);
        }
        ++$this->preHandleCalls;
    }

    public function postHandle(CacheEvent $event): void
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        if ($this->postHandleResponse) {
            $event->setResponse($this->postHandleResponse);
        }
        ++$this->postHandleCalls;
    }

    public function preStore(CacheEvent $event): void
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        if ($this->preStoreResponse) {
            $event->setResponse($this->preStoreResponse);
        }
        ++$this->preStoreCalls;
    }

    public function preInvalidate(CacheEvent $event): void
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        if ($this->preInvalidateResponse) {
            $event->setResponse($this->preInvalidateResponse);
        }
        ++$this->preInvalidateCalls;
    }
}

class SimpleListener
{
    public int $calls = 0;

    /**
     * To do assertions.
     */
    private EventDispatchingHttpCacheTestCase $test;

    /**
     * The kernel to ensure the event carries the correct kernel.
     */
    private CacheInvalidation $kernel;

    /**
     * The request to ensure the event carries the correct request.
     */
    private Request $request;

    public function __construct(
        EventDispatchingHttpCacheTestCase $test,
        CacheInvalidation $kernel,
        Request $request
    ) {
        $this->test = $test;
        $this->kernel = $kernel;
        $this->request = $request;
    }

    public function callback(CacheEvent $event): void
    {
        $this->test->assertSame($this->kernel, $event->getKernel());
        $this->test->assertSame($this->request, $event->getRequest());
        ++$this->calls;
    }
}
