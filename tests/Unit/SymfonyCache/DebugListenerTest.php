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
use FOS\HttpCache\SymfonyCache\DebugListener;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private CacheInvalidation&MockInterface $kernel;

    public function setUp(): void
    {
        $this->kernel = \Mockery::mock(CacheInvalidation::class);
    }

    public function testDebugHit(): void
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, [
            'X-Symfony-Cache' => '... fresh ...',
        ]);
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('HIT', $response->headers->get('X-Cache'));
    }

    public function testDebugMiss(): void
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, [
            'X-Symfony-Cache' => '... miss ...',
        ]);
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('MISS', $response->headers->get('X-Cache'));
    }

    public function testDebugUndefined(): void
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, [
            'X-Symfony-Cache' => '... foobar ...',
        ]);
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('UNDETERMINED', $response->headers->get('X-Cache'));
    }

    public function testNoHeader(): void
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200);
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->headers->has('X-Cache'));
    }
}
