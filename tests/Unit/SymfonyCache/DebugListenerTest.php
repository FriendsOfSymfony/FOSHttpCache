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
use FOS\HttpCache\SymfonyCache\DebugListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheInvalidationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    public function setUp()
    {
        $this->kernel = $this
            ->getMockBuilder('FOS\HttpCache\SymfonyCache\CacheInvalidationInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testDebugHit()
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, array(
            'Symfony-Cache' => '... fresh ...',
        ));
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame('HIT', $response->headers->get('Cache'));
    }

    public function testDebugMiss()
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, array(
            'Symfony-Cache' => '... miss ...',
        ));
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame('MISS', $response->headers->get('Cache'));
    }

    public function testDebugUndefined()
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200, array(
            'Symfony-Cache' => '... foobar ...',
        ));
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame('UNDETERMINED', $response->headers->get('Cache'));
    }

    public function testNoHeader()
    {
        $debugListener = new DebugListener();
        $request = Request::create('http://example.com/foo', 'GET');
        $response = new Response('', 200);
        $event = new CacheEvent($this->kernel, $request, $response);

        $debugListener->handleDebug($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertFalse($response->headers->has('Cache'));
    }
}
