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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserContextListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private CacheInvalidation&MockInterface $kernel;

    public function setUp(): void
    {
        $this->kernel = \Mockery::mock(CacheInvalidation::class);
    }

    /**
     * UserContextListener default options to simulate the correct headers.
     */
    public function provideConfigOptions(): array
    {
        $userContextListener = new UserContextListener();
        $ref = new \ReflectionObject($userContextListener);
        $prop = $ref->getProperty('options');
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
    public function testGenerateUserHashNotAllowed(array $arg, array $options): void
    {
        $userContextListener = new UserContextListener($arg);

        $request = new Request();
        $request->headers->set('accept', $options['user_hash_accept_header']);
        $event = new CacheEvent($this->kernel, $request);

        $userContextListener->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Bad Request', $response->getContent());
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testPassingUserHashNotAllowed(array $arg, array $options): void
    {
        $userContextListener = new UserContextListener($arg);

        $request = new Request();
        $request->headers->set($options['user_hash_header'], 'foo');
        $event = new CacheEvent($this->kernel, $request);

        $userContextListener->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Bad Request', $response->getContent());
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testUserHashAnonymous(array $arg, array $options): void
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
            /** @var Response&MockObject $hashResponse */
            $hashResponse = $this->getMockBuilder(Response::class)
                ->setMethods(['prepare'])
                ->getMock();
            $hashResponse->headers->set($options['user_hash_header'], $expectedContextHash);

            $that = $this;
            $kernel = $this->kernel
                ->shouldReceive('handle')
                ->once()
                ->with(
                    \Mockery::on(
                        static function (Request $request) use ($that, $hashRequest) {
                            // we need to call some methods to get the internal fields initialized
                            $request->getMethod();
                            $request->getPathInfo();
                            $hashRequest->server->remove('REQUEST_TIME_FLOAT');
                            $hashRequest->server->remove('REQUEST_TIME');
                            $request->server->remove('REQUEST_TIME_FLOAT');
                            $request->server->remove('REQUEST_TIME');
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
    public function testUserHashUserWithSession(array $arg, array $options): void
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
        /** @var Response&MockObject $hashResponse */
        $hashResponse = $this->getMockBuilder(Response::class)
            ->setMethods(['prepare'])
            ->getMock();
        $hashResponse->headers->set($options['user_hash_header'], $expectedContextHash);

        $that = $this;
        $kernel = $this->kernel
            ->shouldReceive('handle')
            ->once()
            ->with(
                \Mockery::on(
                    static function (Request $request) use ($that, $hashRequest) {
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
     * When the session_name_prefix is set to false, the cookie header is completely ignored.
     *
     * This test does not have authentication headers and thus considers the request anonymous.
     */
    public function testUserHashUserIgnoreCookies(): void
    {
        $userContextListener = new UserContextListener([
            'session_name_prefix' => false,
            'anonymous_hash' => '38015b703d82206ebc01d17a39c727e5',
        ]);

        $sessionId1 = 'my_session_id';
        $cookies = [
            'PHPSESSID' => $sessionId1,
        ];
        $cookieString = "PHPSESSID=$sessionId1";
        $request = Request::create('/foo', 'GET', [], $cookies, [], ['Cookie' => $cookieString]);

        $this->kernel->shouldNotReceive('handle');
        $event = new CacheEvent($this->kernel, $request);

        $userContextListener->preHandle($event);
        $response = $event->getResponse();

        $this->assertNull($response);
        $this->assertTrue($request->headers->has('X-User-Context-Hash'));
        $this->assertSame('38015b703d82206ebc01d17a39c727e5', $request->headers->get('X-User-Context-Hash'));
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testUserHashUserWithAuthorizationHeader(array $arg, array $options): void
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

    public function testInvalidConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        new UserContextListener(['foo' => 'bar']);
    }

    public function testHttpMethodParameterOverride(): void
    {
        $userContextListener = new UserContextListener();
        $request = Request::create('/foo', 'POST', ['_method' => 'PUT']);
        $event = new CacheEvent($this->kernel, $request);

        $userContextListener->preHandle($event);
        Request::enableHttpMethodParameterOverride();

        $this->assertSame('PUT', $request->getMethod());
    }
}
