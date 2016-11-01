<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\EventListener\LogListener;
use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\Exception\UnsupportedProxyOperationException;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\Invalidation\TagsInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use FOS\HttpCache\ProxyClient\Varnish;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\RequestException;
use Mockery\Mock;
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CacheInvalidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportsTrue()
    {
        /** @var MockInterface|Varnish $proxyClient */
        $proxyClient = \Mockery::mock(Varnish::class);

        $cacheInvalidator = new CacheInvalidator($proxyClient);

        $this->assertTrue($cacheInvalidator->supports(CacheInvalidator::PATH));
        $this->assertTrue($cacheInvalidator->supports(CacheInvalidator::REFRESH));
        $this->assertTrue($cacheInvalidator->supports(CacheInvalidator::INVALIDATE));
        $this->assertTrue($cacheInvalidator->supports(CacheInvalidator::TAGS));
    }

    public function testSupportsFalse()
    {
        /** @var MockInterface|ProxyClientInterface $proxyClient */
        $proxyClient = \Mockery::mock(ProxyClientInterface::class);

        $cacheInvalidator = new CacheInvalidator($proxyClient);

        $this->assertFalse($cacheInvalidator->supports(CacheInvalidator::PATH));
        $this->assertFalse($cacheInvalidator->supports(CacheInvalidator::REFRESH));
        $this->assertFalse($cacheInvalidator->supports(CacheInvalidator::INVALIDATE));
        $this->assertFalse($cacheInvalidator->supports(CacheInvalidator::TAGS));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSupportsInvalid()
    {
        /** @var MockInterface|ProxyClientInterface $proxyClient */
        $proxyClient = \Mockery::mock(ProxyClientInterface::class);

        $cacheInvalidator = new CacheInvalidator($proxyClient);

        $cacheInvalidator->supports('garbage');
    }

    public function testInvalidatePath()
    {
        /** @var MockInterface|PurgeInterface $purge */
        $purge = \Mockery::mock(PurgeInterface::class)
            ->shouldReceive('purge')->once()->with('/my/route', [])
            ->shouldReceive('purge')->once()->with('/my/route', ['X-Test-Header' => 'xyz'])
            ->shouldReceive('flush')->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($purge);

        $cacheInvalidator
            ->invalidatePath('/my/route')
            ->invalidatePath('/my/route', ['X-Test-Header' => 'xyz'])
            ->flush()
        ;
    }

    public function testRefreshPath()
    {
        $headers = ['X' => 'Y'];
        /** @var MockInterface|RefreshInterface $refresh */
        $refresh = \Mockery::mock(RefreshInterface::class)
            ->shouldReceive('refresh')->once()->with('/my/route', $headers)
            ->shouldReceive('flush')->never()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($refresh);

        $cacheInvalidator
            ->refreshPath('/my/route', $headers)
        ;
    }

    public function testInvalidate()
    {
        $headers = [
            'X-Header' => '^value.*$',
            'Other-Header' => '^a|b|c$',
        ];

        /** @var MockInterface|BanInterface $ban */
        $ban = \Mockery::mock(BanInterface::class)
            ->shouldReceive('ban')
            ->with($headers)
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidate($headers);
    }

    public function testInvalidateTags()
    {
        $tags = [
            'post-8',
            'post-type-2',
        ];

        /** @var MockInterface|TagsInterface $tagHandler */
        $tagHandler = \Mockery::mock(TagsInterface::class)
            ->shouldReceive('invalidateTags')
            ->with($tags)
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($tagHandler);
        $cacheInvalidator->invalidateTags($tags);
    }

    public function testInvalidateRegex()
    {
        /** @var MockInterface|BanInterface $ban */
        $ban = \Mockery::mock(BanInterface::class)
            ->shouldReceive('banPath')
            ->with('/a', 'b', ['example.com'])
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidateRegex('/a', 'b', ['example.com']);
    }

    public function testMethodException()
    {
        /** @var MockInterface|ProxyClientInterface $proxyClient */
        $proxyClient = \Mockery::mock(ProxyClientInterface::class);
        $cacheInvalidator = new CacheInvalidator($proxyClient);
        try {
            $cacheInvalidator->invalidatePath('/');
            $this->fail('Expected exception');
        } catch (UnsupportedProxyOperationException $e) {
            // success
        }
        try {
            $cacheInvalidator->refreshPath('/');
            $this->fail('Expected exception');
        } catch (UnsupportedProxyOperationException $e) {
            // success
        }
        try {
            $cacheInvalidator->invalidate([]);
            $this->fail('Expected exception');
        } catch (UnsupportedProxyOperationException $e) {
            // success
        }
        try {
            $cacheInvalidator->invalidateRegex('/');
            $this->fail('Expected exception');
        } catch (UnsupportedProxyOperationException $e) {
            // success
        }
        try {
            $cacheInvalidator->invalidateTags([]);
            $this->fail('Expected exception');
        } catch (UnsupportedProxyOperationException $e) {
            // success
        }
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\ExceptionCollection
     */
    public function testProxyClientExceptionsAreLogged()
    {
        /** @var MockInterface|RequestInterface $failedRequest */
        $failedRequest = \Mockery::mock(RequestInterface::class)
            ->shouldReceive('getHeaderLine')->with('Host')->andReturn('127.0.0.1')
            ->getMock();
        $clientException = new RequestException('Couldn\'t connect to host', $failedRequest);

        $unreachableException = ProxyUnreachableException::proxyUnreachable($clientException);

        $response = \Mockery::mock(ResponseInterface::class)
            ->shouldReceive('getStatusCode')->andReturn(403)
            ->shouldReceive('getReasonPhrase')->andReturn('Forbidden')
            ->getMock();
        $responseException = ProxyResponseException::proxyResponse(new HttpException('test', $failedRequest, $response));

        $exceptions = new ExceptionCollection();
        $exceptions->add($unreachableException)->add($responseException);

        /** @var MockInterface|ProxyClientInterface $proxyClient */
        $proxyClient = \Mockery::mock(ProxyClientInterface::class)
            ->shouldReceive('flush')->once()->andThrow($exceptions)
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($proxyClient);

        $logger = \Mockery::mock(LoggerInterface::class)
            ->shouldReceive('log')->once()
            ->with(
                'critical',
                'Request to caching proxy at 127.0.0.1 failed with message "Couldn\'t connect to host"',
                ['exception' => $unreachableException]
            )
            ->shouldReceive('log')->once()
            ->with(
                'critical',
                '403 error response "Forbidden" from caching proxy',
                ['exception' => $responseException]
            )
            ->getMock();

        $cacheInvalidator->getEventDispatcher()->addSubscriber(new LogListener($logger));

        $cacheInvalidator
            ->flush()
        ;
    }

    public function testEventDispatcher()
    {
        /** @var MockInterface|Varnish $proxyClient */
        $proxyClient = \Mockery::mock(Varnish::class);

        $cacheInvalidator = new CacheInvalidator($proxyClient);
        $eventDispatcher = new EventDispatcher();
        $cacheInvalidator->setEventDispatcher($eventDispatcher);
        $this->assertSame($eventDispatcher, $cacheInvalidator->getEventDispatcher());
    }

    public function testEventDispatcherImmutable()
    {
        /** @var MockInterface|Varnish $proxyClient */
        $proxyClient = \Mockery::mock(Varnish::class);

        $cacheInvalidator = new CacheInvalidator($proxyClient);
        $eventDispatcher = new EventDispatcher();
        $cacheInvalidator->setEventDispatcher($eventDispatcher);
        $this->setExpectedException(\Exception::class);
        $cacheInvalidator->setEventDispatcher($eventDispatcher);
    }
}
