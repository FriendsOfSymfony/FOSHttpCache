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
use FOS\HttpCache\SymfonyCache\UserContextListener;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserContextListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheInvalidation|MockInterface
     */
    private $kernel;

    public function setUp()
    {
        $this->kernel = \Mockery::mock(CacheInvalidation::class);
    }

    /**
     * UserContextListener default options to simulate the correct headers.
     *
     * @return array
     */
    public function provideConfigOptions()
    {
        $userContextListener = new UserContextListener();
        $ref = new \ReflectionObject($userContextListener);
        $prop = $ref->getProperty('options');
        $prop->setAccessible(true);
        $options = $prop->getValue($userContextListener);

        $custom = [
            'user_hash_uri' => '/test-uri',
            'user_hash_header' => 'test/header',
            'user_hash_accept_header' => 'test accept',
            'anonymous_hash' => 'test hash',
        ];

        return [
            [[], $options],
            [$custom, $custom + $options],
        ];
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testGenerateUserHashNotAllowed($arg, $options)
    {
        $userContextListener = new UserContextListener($arg);

        $request = new Request();
        $request->headers->set('accept', $options['user_hash_accept_header']);
        $event = new CacheEvent($this->kernel, $request);

        $userContextListener->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testPassingUserHashNotAllowed($arg, $options)
    {
        $userContextListener = new UserContextListener($arg);

        $request = new Request();
        $request->headers->set($options['user_hash_header'], 'foo');
        $event = new CacheEvent($this->kernel, $request);

        $userContextListener->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testUserHashAnonymous($arg, $options)
    {
        $userContextListener = new UserContextListener($arg);
        $request = new Request();

        if ($options['anonymous_hash']) {
            $event = new CacheEvent($this->kernel, $request);
            $userContextListener->preHandle($event);

            $this->assertTrue($request->headers->has($options['user_hash_header']));
            $this->assertSame($options['anonymous_hash'], $request->headers->get($options['user_hash_header']));
        } else {
            $hashRequest = Request::create($options['user_hash_uri'], $options['user_hash_method'], [], [], [], $request->server->all());
            $hashRequest->attributes->set('internalRequest', true);
            $hashRequest->headers->set('Accept', $options['user_hash_accept_header']);
            // Ensure request properties have been filled up.
            $hashRequest->getPathInfo();
            $hashRequest->getMethod();

            $expectedContextHash = 'my_generated_hash';
            // Just avoid the response to modify the request object, otherwise it's impossible to test objects equality.
            /** @var \Symfony\Component\HttpFoundation\Response|\PHPUnit_Framework_MockObject_MockObject $hashResponse */
            $hashResponse = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Response')
                ->setMethods(['prepare'])
                ->getMock();
            $hashResponse->headers->set($options['user_hash_header'], $expectedContextHash);

            $that = $this;
            $kernel = $this->kernel
                ->shouldReceive('handle')
                ->once()
                ->with(
                    \Mockery::on(
                        function (Request $request) use ($that, $hashRequest) {
                            // we need to call some methods to get the internal fields initialized
                            $request->getMethod();
                            $request->getPathInfo();
                            $that->assertEquals($hashRequest, $request);
                            $that->assertCount(0, $request->cookies->all());

                            return true;
                        }
                    )
                )
                ->andReturn($hashResponse)
                ->getMock();

            $event = new CacheEvent($kernel, $request);
            $userContextListener->preHandle($event);

            $this->assertTrue($request->headers->has($options['user_hash_header']));
            $this->assertSame($expectedContextHash, $request->headers->get($options['user_hash_header']));
        }

        $response = $event->getResponse();

        $this->assertNull($response);
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testUserHashUserWithSession($arg, $options)
    {
        $userContextListener = new UserContextListener($arg);

        $sessionId1 = 'my_session_id';
        $sessionId2 = 'another_session_id';
        $cookies = [
            'PHPSESSID' => $sessionId1,
            'PHPSESSIDsdiuhsdf4535d4f' => $sessionId2,
            'foo' => 'bar',
        ];
        $cookieString = "PHPSESSID=$sessionId1; foo=bar; PHPSESSIDsdiuhsdf4535d4f=$sessionId2";
        $request = Request::create('/foo', 'GET', [], $cookies, [], ['Cookie' => $cookieString]);

        $hashRequest = Request::create($options['user_hash_uri'], $options['user_hash_method'], [], [], [], $request->server->all());
        $hashRequest->attributes->set('internalRequest', true);
        $hashRequest->headers->set('Accept', $options['user_hash_accept_header']);
        $hashRequest->headers->set('Cookie', "PHPSESSID=$sessionId1; PHPSESSIDsdiuhsdf4535d4f=$sessionId2");
        $hashRequest->cookies->set('PHPSESSID', $sessionId1);
        $hashRequest->cookies->set('PHPSESSIDsdiuhsdf4535d4f', $sessionId2);
        // Ensure request properties have been filled up.
        $hashRequest->getPathInfo();
        $hashRequest->getMethod();

        $expectedContextHash = 'my_generated_hash';
        // Just avoid the response to modify the request object, otherwise it's impossible to test objects equality.
        /** @var \Symfony\Component\HttpFoundation\Response|\PHPUnit_Framework_MockObject_MockObject $hashResponse */
        $hashResponse = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Response')
            ->setMethods(['prepare'])
            ->getMock();
        $hashResponse->headers->set($options['user_hash_header'], $expectedContextHash);

        $that = $this;
        $kernel = $this->kernel
            ->shouldReceive('handle')
            ->once()
            ->with(
                \Mockery::on(
                    function (Request $request) use ($that, $hashRequest) {
                        // we need to call some methods to get the internal fields initialized
                        $request->getMethod();
                        $request->getPathInfo();
                        $that->assertEquals($hashRequest, $request);
                        $that->assertCount(2, $request->cookies->all());

                        return true;
                    }
                )
            )
            ->andReturn($hashResponse)
            ->getMock();

        $event = new CacheEvent($kernel, $request);

        $userContextListener->preHandle($event);
        $response = $event->getResponse();

        $this->assertNull($response);
        $this->assertTrue($request->headers->has($options['user_hash_header']));
        $this->assertSame($expectedContextHash, $request->headers->get($options['user_hash_header']));
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testUserHashUserWithAuthorizationHeader($arg, $options)
    {
        $userContextListener = new UserContextListener($arg);

        // The foo cookie should not be available in the eventual hash request anymore
        $request = Request::create('/foo', 'GET', [], ['foo' => 'bar'], [], ['HTTP_AUTHORIZATION' => 'foo']);

        $hashRequest = Request::create($options['user_hash_uri'], $options['user_hash_method'], [], [], [], $request->server->all());
        $hashRequest->attributes->set('internalRequest', true);
        $hashRequest->headers->set('Accept', $options['user_hash_accept_header']);

        // Ensure request properties have been filled up.
        $hashRequest->getPathInfo();
        $hashRequest->getMethod();

        $expectedContextHash = 'my_generated_hash';
        $hashResponse = new Response();
        $hashResponse->headers->set($options['user_hash_header'], $expectedContextHash);

        $that = $this;
        $kernel = $this->kernel
            ->shouldReceive('handle')
            ->once()
            ->with(
                \Mockery::on(
                    function (Request $request) use ($that, $hashRequest) {
                        // we need to call some methods to get the internal fields initialized
                        $request->getMethod();
                        $request->getPathInfo();
                        $that->assertEquals($hashRequest, $request);
                        $that->assertCount(0, $request->cookies->all());

                        return true;
                    }
                )
            )
            ->andReturn($hashResponse)
            ->getMock();

        $event = new CacheEvent($kernel, $request);

        $userContextListener->preHandle($event);
        $response = $event->getResponse();

        $this->assertNull($response);
        $this->assertTrue($request->headers->has($options['user_hash_header']));
        $this->assertSame($expectedContextHash, $request->headers->get($options['user_hash_header']));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage does not exist
     */
    public function testInvalidConfiguration()
    {
        new UserContextListener(['foo' => 'bar']);
    }
}
