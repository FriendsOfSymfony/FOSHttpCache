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
use FOS\HttpCache\SymfonyCache\CacheInvalidationInterface;
use FOS\HttpCache\SymfonyCache\CustomTtlListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomTtlListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheInvalidationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    public function setUp()
    {
        $this->kernel = $this
            ->getMockBuilder(CacheInvalidationInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testCustomTtl()
    {
        $ttlListener = new CustomTtlListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, array(
            'X-Reverse-Proxy-TTL' => '120',
            'Cache-Control' => 's-maxage=60, max-age=30',
        ));
        $event = new CacheEvent($this->kernel, $request, $response);

        $ttlListener->useCustomTtl($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame('120', $response->headers->getCacheControlDirective('s-maxage'));
        $this->assertSame('60', $response->headers->get(CustomTtlListener::SMAXAGE_BACKUP));
    }

    public function testCustomTtlNoSmaxage()
    {
        $ttlListener = new CustomTtlListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, array(
            'X-Reverse-Proxy-TTL' => '120',
            'Cache-Control' => 'max-age=30',
        ));
        $event = new CacheEvent($this->kernel, $request, $response);

        $ttlListener->useCustomTtl($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame('120', $response->headers->getCacheControlDirective('s-maxage'));
        $this->assertSame('false', $response->headers->get(CustomTtlListener::SMAXAGE_BACKUP));
    }

    public function testCleanup()
    {
        $ttlListener = new CustomTtlListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, array(
            'X-Reverse-Proxy-TTL' => '120',
            'Cache-Control' => 's-maxage=120, max-age=30',
            CustomTtlListener::SMAXAGE_BACKUP => '60',
        ));
        $event = new CacheEvent($this->kernel, $request, $response);

        $ttlListener->cleanResponse($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertTrue($response->headers->hasCacheControlDirective('s-maxage'));
        $this->assertSame('60', $response->headers->getCacheControlDirective('s-maxage'));
        $this->assertFalse($response->headers->has('X-Reverse-Proxy-TTL'));
        $this->assertFalse($response->headers->has(CustomTtlListener::SMAXAGE_BACKUP));
    }

    public function testCleanupNoSmaxage()
    {
        $ttlListener = new CustomTtlListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, array(
            'X-Reverse-Proxy-TTL' => '120',
            'Cache-Control' => 's-maxage: 120, max-age: 30',
            CustomTtlListener::SMAXAGE_BACKUP => 'false',
        ));
        $event = new CacheEvent($this->kernel, $request, $response);

        $ttlListener->cleanResponse($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertFalse($response->headers->hasCacheControlDirective('s_maxage'));
        $this->assertFalse($response->headers->has('X-Reverse-Proxy-TTL'));
        $this->assertFalse($response->headers->has(CustomTtlListener::SMAXAGE_BACKUP));
    }
}
