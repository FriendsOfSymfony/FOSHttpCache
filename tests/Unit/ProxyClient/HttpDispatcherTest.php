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
use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\Exception\InvalidUrlException;
use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\ProxyClient\HttpDispatcher;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\NetworkException;
use Http\Client\HttpAsyncClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Http\Promise\Promise;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpDispatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private Client $httpClient;

    private RequestFactoryInterface $requestFactory;

    private UriFactoryInterface $uriFactory;

    protected function setUp(): void
    {
        $this->httpClient = new Client();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->uriFactory = Psr17FactoryDiscovery::findUriFactory();
    }

    /**
     * @param \Exception  $exception Exception thrown by HTTP client
     * @param string      $type      The returned exception class to be expected
     * @param string|null $message   Optional exception message to match against
     */
    #[PHPUnit\DataProvider('exceptionProvider')]
    public function testExceptions(\Exception $exception, string $type, ?string $message = null): void
    {
        $this->doTestException($exception, $type, $message);
    }

    /**
     * Ensure that exceptions by HTTP clients that do not conform to the final standard are nonetheless correctly handled.
     *
     * @group legacy
     */
    public function testLegacyException(): void
    {
        $this->doTestException(new \Exception('something went completely wrong'), InvalidArgumentException::class, 'something went completely wrong');
    }

    private function doTestException(\Exception $exception, string $type, string $message): void
    {
        $this->httpClient->addException($exception);
        $httpDispatcher = new HttpDispatcher(
            ['127.0.0.1:123'],
            'my_hostname.dev',
            $this->httpClient
        );
        $httpDispatcher->invalidate($this->requestFactory->createRequest('PURGE', '/path'));

        try {
            $httpDispatcher->flush();
            $this->fail('Should have aborted with an exception');
        } catch (ExceptionCollection $exceptions) {
            $this->assertCount(1, $exceptions);
            $this->assertInstanceOf($type, $exceptions->getFirst());
            if ($message) {
                $this->assertStringContainsString($message, $exceptions->getFirst()->getMessage());
            }
        }

        // Queue must now be empty, so exception above must not be thrown again.
        $httpDispatcher->invalidate($this->requestFactory->createRequest('GET', '/path'));
        $httpDispatcher->flush();
    }

    public static function exceptionProvider(): array
    {
        /** @var RequestInterface $request */
        $request = \Mockery::mock(RequestInterface::class)
            ->shouldReceive('getHeaderLine')
            ->with('Host')
            ->andReturn('bla.com')
            ->getMock()
        ;

        /** @var ResponseInterface $response */
        $response = \Mockery::mock(ResponseInterface::class)
            ->shouldReceive('getStatusCode')
            ->andReturn('400')
            ->getMock()
            ->shouldReceive('getReasonPhrase')
            ->andReturn('Test')
            ->getMock()
        ;

        return [
            [
                new HttpException('test', $request, $response),
                ProxyResponseException::class,
                '400',
            ],
            [
                new NetworkException('test', $request),
                ProxyUnreachableException::class,
                'bla.com',
            ],
        ];
    }

    public function testMissingHostExceptionIsThrown(): void
    {
        $this->expectException(MissingHostException::class);
        $this->expectExceptionMessage('cannot be invalidated without a host');

        $httpDispatcher = new HttpDispatcher(
            ['127.0.0.1:123'],
            '',
            $this->httpClient
        );

        $request = $this->requestFactory->createRequest('PURGE', '/path/without/hostname');
        $httpDispatcher->invalidate($request);
    }

    public function testBanWithoutBaseUri(): void
    {
        $httpDispatcher = new HttpDispatcher(
            ['127.0.0.1:123'],
            '',
            $this->httpClient
        );

        $request = $this->requestFactory->createRequest('BAN', '/')->withHeader('X-Url', '/foo/.*');
        $httpDispatcher->invalidate($request, false);
        $httpDispatcher->flush();

        $requests = $this->getRequests();
        $this->assertSame('127.0.0.1:123', $requests[0]->getHeaderLine('Host'));
    }

    public function testSetBasePathWithHost(): void
    {
        $httpDispatcher = new HttpDispatcher(
            ['127.0.0.1'],
            'fos.lo',
            $this->httpClient
        );

        $request = $this->requestFactory->createRequest('PURGE', '/path');
        $httpDispatcher->invalidate($request);
        $httpDispatcher->flush();

        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
    }

    public function testServerWithUserInfo(): void
    {
        $httpDispatcher = new HttpDispatcher(
            ['http://userone:passone@127.0.0.1', 'http://127.0.0.2', 'http://usertwo:passtwo@127.0.0.2'],
            'fos.lo',
            $this->httpClient
        );

        $request = $this->requestFactory->createRequest('PURGE', '/path');
        $httpDispatcher->invalidate($request);
        $httpDispatcher->flush();

        $requests = $this->getRequests();

        $this->assertCount(3, $requests);
        $this->assertEquals('userone:passone', $requests[0]->getUri()->getUserInfo());
        $this->assertEquals('', $requests[1]->getUri()->getUserInfo());
        $this->assertEquals('usertwo:passtwo', $requests[2]->getUri()->getUserInfo());
    }

    public function testSetBasePathWithPath(): void
    {
        $httpDispatcher = new HttpDispatcher(
            ['127.0.0.1:8080'],
            'http://fos.lo/my/path',
            $this->httpClient
        );
        $request = $this->requestFactory->createRequest('PURGE', '/append');
        $httpDispatcher->invalidate($request);
        $httpDispatcher->flush();

        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
        $this->assertEquals('http://127.0.0.1:8080/my/path/append', (string) $requests[0]->getUri());
    }

    public function testSetServersDefaultSchemeIsAdded(): void
    {
        $httpDispatcher = new HttpDispatcher(['127.0.0.1'], 'fos.lo', $this->httpClient);
        $request = $this->requestFactory->createRequest('PURGE', '/some/path');
        $httpDispatcher->invalidate($request);
        $httpDispatcher->flush();

        $requests = $this->getRequests();
        $this->assertEquals('http://127.0.0.1/some/path', $requests[0]->getUri());
    }

    public function testSchemeIsAdded(): void
    {
        $httpDispatcher = new HttpDispatcher(['127.0.0.1'], 'fos.lo', $this->httpClient);
        $uri = $this->uriFactory->createUri('/some/path')->withHost('goo.bar');
        $request = $this->requestFactory->createRequest('PURGE', $uri);
        $httpDispatcher->invalidate($request);
        $httpDispatcher->flush();

        $requests = $this->getRequests();
        $this->assertEquals('http://127.0.0.1/some/path', $requests[0]->getUri());
    }

    public function testPortIsAdded(): void
    {
        $httpDispatcher = new HttpDispatcher(['127.0.0.1:8080'], 'fos.lo', $this->httpClient);
        $request = $this->requestFactory->createRequest('PURGE', '/some/path');
        $httpDispatcher->invalidate($request);
        $httpDispatcher->flush();

        $requests = $this->getRequests();
        $this->assertEquals('http://127.0.0.1:8080/some/path', $requests[0]->getUri());
    }

    public function testSetServersThrowsInvalidUrlException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('URL "http:///this is no url" is invalid.');

        new HttpDispatcher(['http:///this is no url']);
    }

    public function testSetServersThrowsWeirdInvalidUrlException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('"this ://is no url" is invalid.');

        new HttpDispatcher(['this ://is no url']);
    }

    public function testSetServersThrowsInvalidServerException(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('Server "http://127.0.0.1:80/some/path" is invalid. Only scheme, user, pass, host, port URL parts are allowed');

        new HttpDispatcher(['http://127.0.0.1:80/some/path']);
    }

    public function testFlushEmpty(): void
    {
        $httpDispatcher = new HttpDispatcher(
            ['127.0.0.1', '127.0.0.2'],
            'fos.lo',
            $this->httpClient
        );
        $this->assertEquals(0, $httpDispatcher->flush());

        $this->assertCount(0, $this->httpClient->getRequests());
    }

    public function testFlushCountSuccess(): void
    {
        $httpClient = \Mockery::mock(HttpAsyncClient::class)
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
                \Mockery::mock(Promise::class)
                    ->shouldReceive('wait')
                    ->times(4)
//                    ->andReturnNull()
//                    ->shouldReceive('getState')
//                    ->between(4, 4)
//                    ->andReturn(Promise::FULFILLED)
                    ->getMock()
            )
            ->getMock();

        $httpDispatcher = new HttpDispatcher(
            ['127.0.0.1', '127.0.0.2'],
            'fos.lo',
            $httpClient
        );
        $httpDispatcher->invalidate($this->requestFactory->createRequest('PURGE', '/a'));
        $httpDispatcher->invalidate($this->requestFactory->createRequest('PURGE', '/b'));

        $this->assertEquals(
            2,
            $httpDispatcher->flush()
        );
    }

    public function testEliminateDuplicates(): void
    {
        $httpClient = \Mockery::mock(HttpAsyncClient::class)
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
                \Mockery::mock(Promise::class)
                    ->shouldReceive('wait')
                    ->times(4)
                    ->getMock()
            )
            ->getMock();

        $httpDispatcher = new HttpDispatcher(['127.0.0.1', '127.0.0.2'], 'fos.lo', $httpClient);
        $httpDispatcher->invalidate(
            $this->requestFactory->createRequest('PURGE', '/c')->withHeader('a', 'b')->withHeader('c', 'd')
        );
        $httpDispatcher->invalidate(
            // same request (header order is not significant)
            $this->requestFactory->createRequest('PURGE', '/c')->withHeader('c', 'd')->withHeader('a', 'b')
        );
        // different request as headers different
        $httpDispatcher->invalidate($this->requestFactory->createRequest('PURGE', '/c'));
        $httpDispatcher->invalidate($this->requestFactory->createRequest('PURGE', '/c'));

        $this->assertEquals(
            2,
            $httpDispatcher->flush()
        );
    }

    /**
     * @return RequestInterface[]
     */
    protected function getRequests(): array
    {
        return $this->httpClient->getRequests();
    }
}
