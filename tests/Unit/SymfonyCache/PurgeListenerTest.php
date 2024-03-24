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
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class PurgeListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * This tests a sanity check in the AbstractControlledListener.
     */
    public function testConstructorOverspecified(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You may not set both a request matcher and an IP');
        new PurgeListener([
            'client_matcher' => new PathRequestMatcher('/forbidden'),
            'client_ips' => ['1.2.3.4'],
        ]);
    }

    public function testPurgeAllowed(): void
    {
        /** @var StoreInterface $store */
        $store = \Mockery::mock(StoreInterface::class)
            ->shouldReceive('purge')
            ->once()
            ->with('http://example.com/foo')
            ->andReturn(true)
            ->getMock();
        $kernel = $this->getKernelMock($store);

        $purgeListener = new PurgeListener();
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($kernel, $request);

        $purgeListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testClearCache(): void
    {
        if (!class_exists(Psr6Store::class)) {
            $this->markTestSkipped('Needs PSR-6 store to be installed.');
        }

        /** @var Psr6Store $store */
        $store = \Mockery::mock(Psr6Store::class)
            ->shouldReceive('clear')
            ->once()
            ->getMock();
        $kernel = $this->getKernelMock($store);

        $purgeListener = new PurgeListener();
        $request = Request::create('http://example.com/', 'PURGE');
        $request->headers->set('Clear-Cache', 'true');
        $event = new CacheEvent($kernel, $request);

        $purgeListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testClearCacheWithoutPsr6Store(): void
    {
        /** @var StoreInterface $store */
        $store = \Mockery::mock(StoreInterface::class);
        $kernel = $this->getKernelMock($store);
        $purgeListener = new PurgeListener();
        $request = Request::create('http://example.com/', 'PURGE');
        $request->headers->set('Clear-Cache', 'true');
        $event = new CacheEvent($kernel, $request);
        $purgeListener->handlePurge($event);
        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Store must be an instance of Toflar\Psr6HttpCacheStore\ClearableInterface. Please check your proxy configuration.', $response->getContent());
    }

    public function testPurgeAllowedMiss(): void
    {
        /** @var StoreInterface $store */
        $store = \Mockery::mock(StoreInterface::class)
            ->shouldReceive('purge')
            ->once()
            ->with('http://example.com/foo')
            ->andReturn(false)
            ->getMock();
        $kernel = $this->getKernelMock($store);

        $purgeListener = new PurgeListener();
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($kernel, $request);

        $purgeListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPurgeForbiddenMatcher(): void
    {
        $kernel = $this->getUnusedKernelMock();

        $matcher = new PathRequestMatcher('/forbidden');
        $purgeListener = new PurgeListener(['client_matcher' => $matcher]);
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($kernel, $request);

        $purgeListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testPurgeForbiddenIp(): void
    {
        $kernel = $this->getUnusedKernelMock();

        $purgeListener = new PurgeListener(['client_ips' => '1.2.3.4']);
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($kernel, $request);

        $purgeListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * Configuring the method to something else should make this listener skip the request.
     */
    public function testOtherMethod(): void
    {
        $kernel = $this->getUnusedKernelMock();
        $matcher = \Mockery::mock(RequestMatcherInterface::class)
            ->shouldNotReceive('isRequestAllowed')
            ->getMock();

        $purgeListener = new PurgeListener([
            'client_matcher' => $matcher,
            'purge_method' => 'FOO',
        ]);
        $request = Request::create('http://example.com/foo', 'PURGE');
        $event = new CacheEvent($kernel, $request);

        $purgeListener->handlePurge($event);
        $this->assertNull($event->getResponse());
    }

    public function testInvalidConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        new PurgeListener(['stuff' => '1.2.3.4']);
    }

    private function getKernelMock(StoreInterface $store): MockInterface&CacheInvalidation
    {
        return \Mockery::mock(CacheInvalidation::class)
            ->shouldReceive('getStore')
            ->once()
            ->andReturn($store)
            ->getMock();
    }

    private function getUnusedKernelMock(): CacheInvalidation&MockInterface
    {
        return \Mockery::mock(CacheInvalidation::class)
            ->shouldNotReceive('getStore')
            ->getMock();
    }
}
