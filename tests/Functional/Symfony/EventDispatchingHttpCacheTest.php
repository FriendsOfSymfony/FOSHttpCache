<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\Symfony;

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\CustomTtlListener;
use FOS\HttpCache\SymfonyCache\DebugListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\RefreshListener;
use FOS\HttpCache\SymfonyCache\UserContextListener;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @group symfony
 */
class EventDispatchingHttpCacheTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEventListeners(): void
    {
        $request = new Request();
        $expectedResponse = new Response();
        $expectedResponse->headers->set('X-Reverse-Proxy-TTL', 60);
        $expectedResponse->headers->set('X-Cache-Tags', 'foo, bar');

        $httpKernel = \Mockery::mock(HttpKernelInterface::class)
            ->shouldReceive('handle')
            ->withArgs([$request, HttpKernelInterface::MAIN_REQUEST, true])
            ->andReturn($expectedResponse)
            ->getMock();
        $store = \Mockery::mock(StoreInterface::class)
            // need to declare the cleanup function explicitly to avoid issue between register_shutdown_function and mockery
            ->shouldReceive('cleanup')
            ->atMost(1)
            ->getMock();
        $kernel = new AppCache($httpKernel, $store);
        $kernel->addSubscriber(new CustomTtlListener());
        $kernel->addSubscriber(new DebugListener());
        $kernel->addSubscriber(new PurgeListener());
        $kernel->addSubscriber(new RefreshListener());
        $kernel->addSubscriber(new UserContextListener([
            // avoid having to provide mocking for the hash lookup
            // we already test anonymous hash lookup in the UserContextListener unit test
            'anonymous_hash' => 'abcdef',
        ]));
        $kernel->addSubscriber(new CleanupCacheTagsListener());

        $response = $kernel->handle($request);
        $this->assertSame($expectedResponse, $response);
        $this->assertFalse($response->headers->has('X-Reverse-Proxy-TTL'));
        $this->assertFalse($response->headers->has('X-Cache-Tags'));
    }
}

class AppCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    /**
     * Made public to allow event listeners to do refresh operations.
     *
     * {@inheritdoc}
     */
    public function fetch(Request $request, $catch = false): Response
    {
        return parent::fetch($request, $catch);
    }
}
