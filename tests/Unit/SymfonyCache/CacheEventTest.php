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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CacheEventTest extends TestCase
{
    /**
     * @var CacheInvalidation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    public function setUp()
    {
        $this->kernel = $this->createMock(CacheInvalidation::class);
    }

    public function testEventGetters()
    {
        $request = Request::create('/');

        $event = new CacheEvent($this->kernel, $request);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($request, $event->getRequest());
        $this->assertNull($event->getResponse());
        $this->assertSame(KernelInterface::MASTER_REQUEST, $event->getRequestType());

        $response = new Response();

        $event = new CacheEvent($this->kernel, $request, $response, HttpKernelInterface::SUB_REQUEST);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($response, $event->getResponse());
        $this->assertSame(KernelInterface::SUB_REQUEST, $event->getRequestType());
    }
}
