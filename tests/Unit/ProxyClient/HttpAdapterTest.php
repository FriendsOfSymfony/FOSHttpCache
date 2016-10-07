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
use FOS\HttpCache\ProxyClient\Http\HttpAdapter;
use Http\Message\MessageFactory;
use Http\Mock\Client;
use Http\Client\Exception\RequestException;
use Http\Discovery\MessageFactoryDiscovery;
use Psr\Http\Message\RequestInterface;

/**
 * Testing the HTTP Adapter.
 */
class HttpAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock client.
     *
     * @var Client
     */
    private $client;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    protected function setUp()
    {
        $this->client = new Client();
        $this->messageFactory = MessageFactoryDiscovery::find();
    }

    /**
     * @dataProvider exceptionProvider
     *
     * @param \Exception $exception Exception thrown by HTTP client
     * @param string     $type      The returned exception class to be expected
     * @param string     $message   Optional exception message to match against
     */
    public function testExceptions(\Exception $exception, $type, $message = null)
    {
        $this->client->addException($exception);
        $httpAdapter = new HttpAdapter(
            ['127.0.0.1:123'],
            'my_hostname.dev',
            $this->client
        );
        $httpAdapter->invalidate($this->messageFactory->createRequest('PURGE', '/path'));

        try {
            $httpAdapter->flush();
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

        // Queue must now be empty, so exception above must not be thrown again.
        $httpAdapter->invalidate($this->messageFactory->createRequest('GET', '/path'));
        $httpAdapter->flush();
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
        $unreachableException = new RequestException('test', $request);

        return [
            [
                $unreachableException,
                '\FOS\HttpCache\Exception\ProxyUnreachableException',
                'bla.com',
            ],
        ];
    }

    public function testErrorResponsesAreConvertedToExceptions()
    {
        $this->markTestSkipped('Default php-http behaviour is not to exceptions');

        $response = $this->messageFactory->createResponse(
            405,
            'Not allowed'
        );
        $this->client->addResponse($response);

        $httpAdapter = new HttpAdapter(['127.0.0.1:123'], ['base_uri' => 'my_hostname.dev'], $this->client);
        $httpAdapter->invalidate($this->messageFactory->createRequest('PURGE', '/'));
        try {
            $httpAdapter->flush();
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
     * @expectedExceptionMessage is not absolute
     */
    public function testMissingHostExceptionIsThrown()
    {
        $httpAdapter = new HttpAdapter(
            ['127.0.0.1:123'],
            '',
            $this->client
        );

        $request = $this->messageFactory->createRequest('PURGE', '/path/without/hostname');
        $httpAdapter->invalidate($request);
    }

    public function testSetBasePathWithHost()
    {
        $httpAdapter = new HttpAdapter(
            ['127.0.0.1'],
            'fos.lo',
            $this->client
        );

        $request = $this->messageFactory->createRequest('PURGE', '/path');
        $httpAdapter->invalidate($request);
        $httpAdapter->flush();

        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
    }

    public function testSetBasePathWithPath()
    {
        $httpAdapter = new HttpAdapter(
            ['127.0.0.1'],
            'http://fos.lo/my/path',
            $this->client
        );
        $request = $this->messageFactory->createRequest('PURGE', 'append');
        $httpAdapter->invalidate($request);
        $httpAdapter->flush();

        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
        $this->assertEquals('http://127.0.0.1/my/path/append', (string) $requests[0]->getUri());
    }

    public function testSetServersDefaultSchemeIsAdded()
    {
        $httpAdapter = new HttpAdapter(['127.0.0.1'], 'fos.lo', $this->client);
        $request = $this->messageFactory->createRequest('PURGE', '/some/path');
        $httpAdapter->invalidate($request);
        $httpAdapter->flush();

        $requests = $this->getRequests();
        $this->assertEquals('http://127.0.0.1/some/path', $requests[0]->getUri());
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage URL "http:///this is no url" is invalid.
     */
    public function testSetServersThrowsInvalidUrlException()
    {
        new HttpAdapter(['http:///this is no url']);
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage URL "this ://is no url" is invalid.
     */
    public function testSetServersThrowsWeirdInvalidUrlException()
    {
        new HttpAdapter(['this ://is no url']);
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage Server "http://127.0.0.1:80/some/path" is invalid. Only scheme, host, port URL parts are allowed
     */
    public function testSetServersThrowsInvalidServerException()
    {
        new HttpAdapter(['http://127.0.0.1:80/some/path']);
    }

    public function testFlushEmpty()
    {
        $httpAdapter = new HttpAdapter(
            ['127.0.0.1', '127.0.0.2'], 'fos.lo', $this->client
        );
        $this->assertEquals(0, $httpAdapter->flush());

        $this->assertCount(0, $this->client->getRequests());
    }

    public function testFlushCountSuccess()
    {
        $httpClient = \Mockery::mock('\Http\Client\HttpAsyncClient')
            ->shouldReceive('sendAsyncRequest')
            ->times(4)
            ->with(
                \Mockery::on(
                    function (RequestInterface $request) {
                        $this->assertEquals('PURGE', $request->getMethod());

                        return true;
                    }
                )
            )
            ->andReturn(
                \Mockery::mock('\Http\Promise\Promise')
                    ->shouldReceive('wait')
                    ->times(4)
//                    ->andReturnNull()
//                    ->shouldReceive('getState')
//                    ->between(4, 4)
//                    ->andReturn(Promise::FULFILLED)
                    ->getMock()
            )
            ->getMock();

        $httpAdapter = new HttpAdapter(
            ['127.0.0.1', '127.0.0.2'],
            'fos.lo',
            $httpClient
        );
        $httpAdapter->invalidate($this->messageFactory->createRequest('PURGE', '/a'));
        $httpAdapter->invalidate($this->messageFactory->createRequest('PURGE', '/b'));

        $this->assertEquals(
            2,
            $httpAdapter->flush()
        );
    }

    public function testEliminateDuplicates()
    {
        $httpClient = \Mockery::mock('\Http\Client\HttpAsyncClient')
            ->shouldReceive('sendAsyncRequest')
            ->times(4)
            ->with(
                \Mockery::on(
                    function (RequestInterface $request) {
                        $this->assertEquals('PURGE', $request->getMethod());

                        return true;
                    }
                )
            )
            ->andReturn(
                \Mockery::mock('\Http\Promise\Promise')
                    ->shouldReceive('wait')
                    ->times(4)
                    ->getMock()
            )
            ->getMock();

        $httpAdapter = new HttpAdapter(['127.0.0.1', '127.0.0.2'], 'fos.lo', $httpClient);
        $httpAdapter->invalidate(
            $this->messageFactory->createRequest('PURGE', '/c', ['a' => 'b', 'c' => 'd'])
        );
        $httpAdapter->invalidate(
            // same request (header order is not significant)
            $this->messageFactory->createRequest('PURGE', '/c', ['c' => 'd', 'a' => 'b'])
        );
        // different request as headers different
        $httpAdapter->invalidate($this->messageFactory->createRequest('PURGE', '/c'));
        $httpAdapter->invalidate($this->messageFactory->createRequest('PURGE', '/c'));

        $this->assertEquals(
            2,
            $httpAdapter->flush()
        );
    }

    /**
     * @return array|RequestInterface[]
     */
    protected function getRequests()
    {
        return $this->client->getRequests();
    }
}
