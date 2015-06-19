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
use FOS\HttpCache\Test\HttpClient\MockHttpAdapter;
use Http\Adapter\Exception\HttpAdapterException;
use Http\Discovery\MessageFactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use \Mockery;

/**
 * Testing the base methods of the proxy client, using the Varnish client as concrete class.
 */
class AbstractProxyClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockHttpAdapter
     */
    private $client;

    /**
     * @dataProvider exceptionProvider
     *
     * @param \Exception $exception Exception thrown by HTTP adapter.
     * @param string     $type      The returned exception class to be expected.
     * @param string     $message   Optional exception message to match against.
     */
    public function testExceptions(\Exception $exception, $type, $message = null)
    {
        $this->client->setException($exception);
        $varnish = new Varnish(['127.0.0.1:123'], 'my_hostname.dev', $this->client);

        $varnish->purge('/');

        try {
            $varnish->flush();
            $this->fail('Should have aborted with an exception');
        } catch (ExceptionCollection $exceptions) {
            $this->assertCount(1, $exceptions);
            $this->assertInstanceOf($type, $exceptions->getFirst());
            if ($message) {
                $this->assertContains(
                    $message,
                    $exceptions->getFirst()->getMessage()
                );
            }
        }

        $this->client->clear();

        // Queue must now be empty, so exception above must not be thrown again.
        $varnish->purge('/path')->flush();
    }

    public function exceptionProvider()
    {
        // Timeout exception (without response)
        $request = \Mockery::mock('\Psr\Http\Message\RequestInterface')
            ->shouldReceive('getHeaderLine')
            ->with('Host')
            ->andReturn('bla.com')
            ->getMock()
        ;
        $unreachableException = new HttpAdapterException();
        $unreachableException->setRequest($request);

        return [
            [
                $unreachableException,
                '\FOS\HttpCache\Exception\ProxyUnreachableException',
                'bla.com'
            ]
        ];
    }

    public function testErrorResponsesAreConvertedToExceptions()
    {
        $response = MessageFactoryDiscovery::find()->createResponse(
            405,
            'Not allowed'
        );
        $this->client->addResponse($response);

        $varnish = new Varnish(['127.0.0.1:123'], 'my_hostname.dev', $this->client);
        try {
            $varnish->purge('/')->flush();
            $this->fail('Should have aborted with an exception');
        } catch (ExceptionCollection $exceptions) {
            $this->assertCount(1, $exceptions);
            $this->assertEquals(
                '405 error response "Not allowed" from caching proxy',
                $exceptions->getFirst()->getMessage()
            );
        }
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\MissingHostException
     * @expectedExceptionMessage cannot be invalidated without a host
     */
    public function testMissingHostExceptionIsThrown()
    {
        $varnish = new Varnish(['127.0.0.1:123'], null, $this->client);
        $varnish->purge('/path/without/hostname');
    }

    public function testSetBasePathWithHost()
    {
        $varnish = new Varnish(['127.0.0.1'], 'fos.lo', $this->client);
        $varnish->purge('/path')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
    }

    public function testSetBasePathWithPath()
    {
        $varnish = new Varnish(['127.0.0.1'], 'http://fos.lo/my/path', $this->client);
        $varnish->purge('append')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
        $this->assertEquals('http://127.0.0.1/my/path/append', (string) $requests[0]->getUri());
    }

    public function testSetServersDefaultSchemeIsAdded()
    {
        $varnish = new Varnish(['127.0.0.1'], 'fos.lo', $this->client);
        $varnish->purge('/some/path')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('http://127.0.0.1/some/path', $requests[0]->getUri());
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage URL "http:///this is no url" is invalid.
     */
    public function testSetServersThrowsInvalidUrlException()
    {
        new Varnish(['http:///this is no url']);
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage URL "this ://is no url" is invalid.
     */
    public function testSetServersThrowsWeirdInvalidUrlException()
    {
        new Varnish(['this ://is no url']);
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage Server "http://127.0.0.1:80/some/weird/path" is invalid. Only scheme, host, port URL parts are allowed
     */
    public function testSetServersThrowsInvalidServerException()
    {
        new Varnish(['http://127.0.0.1:80/some/weird/path']);
    }

    public function testFlushEmpty()
    {
        $varnish = new Varnish(['127.0.0.1', '127.0.0.2'], 'fos.lo', $this->client);
        $this->assertEquals(0, $varnish->flush());

        $this->assertCount(0, $this->client->getRequests());
    }

    public function testFlushCountSuccess()
    {
        $httpAdapter = \Mockery::mock('\Http\Adapter\HttpAdapter')
            ->shouldReceive('sendRequests')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) {
                        /** @type RequestInterface[] $requests */
                        $this->assertCount(4, $requests);
                        foreach ($requests as $request) {
                            $this->assertEquals('PURGE', $request->getMethod());
                        }

                        return true;
                    }
                )
            )
            ->andReturn([])
            ->getMock();

        $varnish = new Varnish(['127.0.0.1', '127.0.0.2'], 'fos.lo', $httpAdapter);

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
        $client = \Mockery::mock('\Http\Adapter\HttpAdapter')
            ->shouldReceive('sendRequests')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) {
                        /** @type RequestInterface[] $requests */
                        $this->assertCount(4, $requests);
                        foreach ($requests as $request) {
                            $this->assertEquals('PURGE', $request->getMethod());
                        }

                        return true;
                    }
                )
            )
            ->andReturn([])
            ->getMock();

        $varnish = new Varnish(['127.0.0.1', '127.0.0.2'], 'fos.lo', $client);

        $this->assertEquals(
            2,
            $varnish
                ->purge('/c', ['a' => 'b', 'c' => 'd'])
                ->purge('/c', ['c' => 'd', 'a' => 'b']) // same request (header order is not significant)
                ->purge('/c') // different request as headers different
                ->purge('/c')
                ->flush()
        );
    }

    protected function setUp()
    {
        $this->client = new MockHttpAdapter();
    }

    /**
     * @return array|RequestInterface[]
     */
    protected function getRequests()
    {
        return $this->client->getRequests();
    }
}
