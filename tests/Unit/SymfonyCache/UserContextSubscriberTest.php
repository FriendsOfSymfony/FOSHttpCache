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
use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

class UserContextSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    public function setUp()
    {
        $this->kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\HttpCache\HttpCache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * UserContextSubscriber default options to simulate the correct headers.
     *
     * @return array
     */
    public function provideConfigOptions()
    {
        $subscriber = new UserContextSubscriber();
        $ref = new \ReflectionObject($subscriber);
        $prop = $ref->getProperty('options');
        $prop->setAccessible(true);
        $options = $prop->getValue($subscriber);

        $custom = array(
            'user_hash_uri' => '/test-uri',
            'user_hash_header' => 'test/header',
            'user_hash_accept_header' => 'test accept',
            'anonymous_hash' => 'test hash',
        );;

        return array(
            array(array(), $options),
            array($custom, $custom + $options)
        );
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testGenerateUserHashNotAllowed($arg, $options)
    {
        $userContextSubscriber = new UserContextSubscriber($arg);

        $request = new Request();
        $request->headers->set('accept', $options['user_hash_accept_header']);
        $event = new CacheEvent($this->kernel, $request);

        $userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testPassingUserHashNotAllowed($arg, $options)
    {
        $userContextSubscriber = new UserContextSubscriber($arg);

        $request = new Request();
        $request->headers->set($options['user_hash_header'], 'foo');
        $event = new CacheEvent($this->kernel, $request);

        $userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testUserHashAnonymous($arg, $options)
    {
        $userContextSubscriber = new UserContextSubscriber($arg);

        $request = new Request();

        $event = new CacheEvent($this->kernel, $request);

        $userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertNull($response);
        $this->assertTrue($request->headers->has($options['user_hash_header']));
        $this->assertSame($options['anonymous_hash'], $request->headers->get($options['user_hash_header']));
    }

    /**
     * @dataProvider provideConfigOptions
     */
    public function testUserHashUserWithSession($arg, $options)
    {
        $userContextSubscriber = new UserContextSubscriber($arg);

        $catch = true;
        $sessionId1 = 'my_session_id';
        $sessionId2 = 'another_session_id';
        $cookies = array(
            'PHPSESSID' => $sessionId1,
            'PHPSESSIDsdiuhsdf4535d4f' => $sessionId2,
            'foo' => 'bar'
        );
        $cookieString = "PHPSESSID=$sessionId1; foo=bar; PHPSESSIDsdiuhsdf4535d4f=$sessionId2";
        $request = Request::create('/foo', 'GET', array(), $cookies, array(), array('Cookie' => $cookieString));

        $hashRequest = Request::create($options['user_hash_uri'], $options['user_hash_method'], array(), array(), array(), $request->server->all());
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
            ->setMethods(array('prepare'))
            ->getMock();
        $hashResponse->headers->set($options['user_hash_header'], $expectedContextHash );

        $that = $this;
        $this->kernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(function (Request $request) use ($that, $hashRequest) {
                    // we need to call some methods to get the internal fields initialized
                    $request->getMethod();
                    $request->getPathInfo();
                    $that->assertEquals($hashRequest, $request);

                    return true;
                }),
                $catch
            )
            ->will($this->returnValue($hashResponse));

        $event = new CacheEvent($this->kernel, $request);

        $userContextSubscriber->preHandle($event);
        $response = $event->getResponse();

        $this->assertNull($response);
        $this->assertTrue($request->headers->has($options['user_hash_header']));
        $this->assertSame($expectedContextHash, $request->headers->get($options['user_hash_header']));
    }
}
