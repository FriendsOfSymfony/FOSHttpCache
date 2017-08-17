<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\ProxyClient;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\ProxyClient\Varnish;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\Request;
use Mockery;

/**
 * Testing the base methods of the proxy client, using the Varnish client as concrete class.
 */
class AbstractProxyClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockPlugin
     */
    private $mock;

    private $client;

    public function testUnreachableException()
    {
        $mock = new MockPlugin();
        $mock->addException(new CurlException('connect to host'));

        $client = new Client();
        $client->addSubscriber($mock);

        $varnish = new Varnish(array('127.0.0.1:123'), 'my_hostname.dev', $client);

        try {
            $varnish->purge('/paths')->flush();
        } catch (ExceptionCollection $exceptions) {
            $this->assertCount(1, $exceptions);
            $this->assertInstanceOf('\FOS\HttpCache\Exception\ProxyUnreachableException', $exceptions->getFirst());
        }

        $mock->clearQueue();
        $mock->addResponse(new Response(200));

        // Queue must now be empty, so exception above must not be thrown again.
        $varnish->purge('/path')->flush();
    }

    public function curlExceptionProvider()
    {
        $requestException = new RequestException('request');
        $requestException->setRequest(new Request('GET', '/'));

        $curlException = new CurlException('curl');
        $curlException->setRequest(new Request('GET', '/'));

        return array(
            array($curlException, '\FOS\HttpCache\Exception\ProxyUnreachableException'),
            array($requestException, '\FOS\HttpCache\Exception\ProxyResponseException'),
            array(new \InvalidArgumentException('something'), '\InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider curlExceptionProvider
     *
     * @param \Exception $exception the exception that curl should throw
     * @param string     $type      the returned exception class to be expected
     */
    public function testExceptions(\Exception $exception, $type)
    {
        // the guzzle mock plugin does not allow arbitrary exceptions
        // mockery does not provide all methods of the interface
        $collection = new MultiTransferException();
        $collection->setExceptions(array($exception));
        $client = $this->getMock('\Guzzle\Http\ClientInterface');
        $client->expects($this->any())
            ->method('createRequest')
            ->willReturn(new Request('BAN', '/'))
        ;
        $client->expects($this->once())
            ->method('send')
            ->willThrowException($collection)
        ;

        $varnish = new Varnish(array('127.0.0.1:123'), 'my_hostname.dev', $client);

        $varnish->ban(array());

        try {
            $varnish->flush();
            $this->fail('Should have aborted with an exception');
        } catch (ExceptionCollection $exceptions) {
            $this->assertCount(1, $exceptions);
            $this->assertInstanceOf($type, $exceptions->getFirst());
        }
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\MissingHostException
     * @expectedExceptionMessage cannot be invalidated without a host
     */
    public function testMissingHostExceptionIsThrown()
    {
        $varnish = new Varnish(array('127.0.0.1:123'), null, $this->client);
        $varnish->purge('/path/without/hostname');
    }

    public function testSetBasePathWithHost()
    {
        $varnish = new Varnish(array('127.0.0.1'), 'fos.lo', $this->client);
        $varnish->purge('/path')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeader('Host'));
    }

    public function testSetBasePathWithPath()
    {
        $varnish = new Varnish(array('127.0.0.1'), 'http://fos.lo/my/path', $this->client);
        $varnish->purge('append')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeader('Host'));
        $this->assertEquals('http://127.0.0.1/my/path/append', $requests[0]->getUrl());
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     */
    public function testSetBasePathThrowsInvalidUrlSchemeException()
    {
        new Varnish(array('127.0.0.1'), 'https://fos.lo/my/path');
    }

    public function testSetServersDefaultSchemeIsAdded()
    {
        $varnish = new Varnish(array('127.0.0.1'), 'fos.lo', $this->client);
        $varnish->purge('/some/path')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('http://127.0.0.1/some/path', $requests[0]->getUrl());
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage URL "http:///this is no url" is invalid.
     */
    public function testSetServersThrowsInvalidUrlException()
    {
        new Varnish(array('http:///this is no url'));
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage URL "this ://is no url" is invalid.
     */
    public function testSetServersThrowsWeirdInvalidUrlException()
    {
        new Varnish(array('this ://is no url'));
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage Host "https://127.0.0.1" with scheme "https" is invalid
     */
    public function testSetServersThrowsInvalidUrlSchemeException()
    {
        new Varnish(array('https://127.0.0.1'));
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage Server "http://127.0.0.1:80/some/weird/path" is invalid. Only scheme, host, port URL parts are allowed
     */
    public function testSetServersThrowsInvalidServerException()
    {
        new Varnish(array('http://127.0.0.1:80/some/weird/path'));
    }

    public function testFlushEmpty()
    {
        $client = \Mockery::mock('\Guzzle\Http\Client[send]', array('', null))
            ->shouldReceive('send')
            ->never()
            ->getMock()
        ;

        $varnish = new Varnish(array('127.0.0.1', '127.0.0.2'), 'fos.lo', $client);
        $this->assertEquals(0, $varnish->flush());
    }

    public function testFlushCountSuccess()
    {
        $self = $this;
        $client = \Mockery::mock('\Guzzle\Http\Client[send]', array('', null))
            ->shouldReceive('send')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) use ($self) {
                        /* @type Request[] $requests */
                        $self->assertCount(4, $requests);
                        foreach ($requests as $request) {
                            $self->assertEquals('PURGE', $request->getMethod());
                        }

                        return true;
                    }
                )
            )
            ->getMock();

        $varnish = new Varnish(array('127.0.0.1', '127.0.0.2'), 'fos.lo', $client);

        $this->assertEquals(
            2,
            $varnish
                ->purge('/c')
                ->purge('/b')
                ->flush()
        );
    }

    public function testEliminateDuplicates()
    {
        $self = $this;
        $client = \Mockery::mock('\Guzzle\Http\Client[send]', array('', null))
            ->shouldReceive('send')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) use ($self) {
                        /* @type Request[] $requests */
                        $self->assertCount(4, $requests);
                        foreach ($requests as $request) {
                            $self->assertEquals('PURGE', $request->getMethod());
                        }

                        return true;
                    }
                )
            )
            ->getMock();

        $varnish = new Varnish(array('127.0.0.1', '127.0.0.2'), 'fos.lo', $client);

        $this->assertEquals(
            2,
            $varnish
                ->purge('/c', array('a' => 'b', 'c' => 'd'))
                ->purge('/c', array('c' => 'd', 'a' => 'b')) // same request (header order is not significant)
                ->purge('/c') // different request as headers different
                ->purge('/c')
                ->flush()
        );
    }

    protected function setUp()
    {
        $this->mock = new MockPlugin();
        $this->mock->addResponse(new Response(200));
        $this->client = new Client();
        $this->client->addSubscriber($this->mock);
    }

    /**
     * @return array|Request[]
     */
    protected function getRequests()
    {
        return $this->mock->getReceivedRequests();
    }
}
