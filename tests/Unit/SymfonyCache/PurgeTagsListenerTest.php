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
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\SymfonyCache\TaggableStore;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class PurgeTagsListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * This tests a sanity check in the AbstractControlledListener.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You may not set both a request matcher and an IP
     */
    public function testConstructorOverspecified()
    {
        new PurgeTagsListener([
            'client_matcher' => new RequestMatcher('/forbidden'),
            'client_ips' => ['1.2.3.4'],
        ]);
    }

    public function testBadRequestIfWrongStore()
    {
        /** @var StoreInterface $store */
        $store = \Mockery::mock(StoreInterface::class)
            ->shouldReceive('purge')
            ->never()
            ->with('http://example.com/foo')
            ->andReturn(true)
            ->getMock();
        $kernel = $this->getKernelMock($store);

        $purgeTagsListener = new PurgeTagsListener();
        $request = Request::create('http://example.com/foo', 'PURGETAGS');
        $request->headers->set('X-Cache-Tags', 'foobar,other tag');
        $event = new CacheEvent($kernel, $request);

        $purgeTagsListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Store must be an instance of TaggableStore! Check your proxy configuration!', $response->getContent());
    }

    public function testPurgeAllowed()
    {
        /** @var StoreInterface $store */
        $store = \Mockery::mock(TaggableStore::class)
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(['foobar', 'other tag'])
            ->andReturn(true)
            ->getMock();
        $kernel = $this->getKernelMock($store);

        $purgeTagsListener = new PurgeTagsListener();
        $request = Request::create('http://example.com/foo', 'PURGETAGS');
        $request->headers->set('X-Cache-Tags', 'foobar,other tag');
        $event = new CacheEvent($kernel, $request);

        $purgeTagsListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPurgeAllowedMiss()
    {
        /** @var StoreInterface $store */
        $store = \Mockery::mock(TaggableStore::class)
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(['foobar', 'other tag'])
            ->andReturn(false)
            ->getMock();
        $kernel = $this->getKernelMock($store);

        $purgeTagsListener = new PurgeTagsListener();
        $request = Request::create('http://example.com/foo', 'PURGETAGS');
        $request->headers->set('X-Cache-Tags', 'foobar,other tag');
        $event = new CacheEvent($kernel, $request);

        $purgeTagsListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPurgeForbiddenMatcher()
    {
        $kernel = $this->getUnusedKernelMock();

        $matcher = new RequestMatcher('/forbidden');
        $purgeTagsListener = new PurgeTagsListener(['client_matcher' => $matcher]);
        $request = Request::create('http://example.com/foo', 'PURGETAGS');
        $event = new CacheEvent($kernel, $request);

        $purgeTagsListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testPurgeForbiddenIp()
    {
        $kernel = $this->getUnusedKernelMock();

        $purgeTagsListener = new PurgeTagsListener(['client_ips' => '1.2.3.4']);
        $request = Request::create('http://example.com/foo', 'PURGETAGS');
        $event = new CacheEvent($kernel, $request);

        $purgeTagsListener->handlePurge($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * Configuring the method to something else should make this listener skip the request.
     */
    public function testOtherMethod()
    {
        $kernel = $this->getUnusedKernelMock();
        $matcher = \Mockery::mock(RequestMatcher::class)
            ->shouldNotReceive('isRequestAllowed')
            ->getMock();

        $purgeTagsListener = new PurgeTagsListener([
            'client_matcher' => $matcher,
            'purge_tags_method' => 'FOO',
        ]);
        $request = Request::create('http://example.com/foo', 'PURGETAGS');
        $event = new CacheEvent($kernel, $request);

        $purgeTagsListener->handlePurge($event);
        $this->assertNull($event->getResponse());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage does not exist
     */
    public function testInvalidConfiguration()
    {
        new PurgeTagsListener(['stuff' => '1.2.3.4']);
    }

    /**
     * @return CacheInvalidation|MockInterface
     */
    private function getKernelMock(StoreInterface $store)
    {
        return \Mockery::mock(CacheInvalidation::class)
            ->shouldReceive('getStore')
            ->once()
            ->andReturn($store)
            ->getMock();
    }

    /**
     * @return CacheInvalidation|MockInterface
     */
    private function getUnusedKernelMock()
    {
        return \Mockery::mock(CacheInvalidation::class)
            ->shouldNotReceive('getStore')
            ->getMock();
    }
}
