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
use FOS\HttpCache\EventListener\LogSubscriber;
use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\Exception\UnsupportedProxyOperationException;
use FOS\HttpCache\ProxyClient\Varnish;
use \Mockery;

class CacheInvalidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportsTrue()
    {
        $httpCache = new Varnish(array('localhost'));

        $cacheInvalidator = new CacheInvalidator($httpCache);

        $this->assertTrue($cacheInvalidator->supports(CacheInvalidator::PATH));
        $this->assertTrue($cacheInvalidator->supports(CacheInvalidator::REFRESH));
        $this->assertTrue($cacheInvalidator->supports(CacheInvalidator::INVALIDATE));
    }

    public function testSupportsFalse()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\ProxyClient\ProxyClientInterface');

        $cacheInvalidator = new CacheInvalidator($httpCache);

        $this->assertFalse($cacheInvalidator->supports(CacheInvalidator::PATH));
        $this->assertFalse($cacheInvalidator->supports(CacheInvalidator::REFRESH));
        $this->assertFalse($cacheInvalidator->supports(CacheInvalidator::INVALIDATE));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSupportsInvalid()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\ProxyClient\ProxyClientInterface');

        $cacheInvalidator = new CacheInvalidator($httpCache);

        $cacheInvalidator->supports('garbage');
    }

    public function testInvalidatePath()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface')
            ->shouldReceive('purge')->once()->with('/my/route')
            ->shouldReceive('flush')->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($httpCache);

        $cacheInvalidator
            ->invalidatePath('/my/route')
            ->flush()
        ;
    }

    public function testRefreshPath()
    {
        $headers = array('X' => 'Y');
        $httpCache = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface')
            ->shouldReceive('refresh')->once()->with('/my/route', $headers)
            ->shouldReceive('flush')->never()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($httpCache);

        $cacheInvalidator
            ->refreshPath('/my/route', $headers)
        ;
    }

    public function testInvalidate()
    {
        $headers = array(
            'X-Header' => '^value.*$',
            'Other-Header' => '^a|b|c$',
        );

        $ban = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\BanInterface')
            ->shouldReceive('ban')
            ->with($headers)
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidate($headers);
    }

    public function testInvalidateRegex()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\BanInterface')
            ->shouldReceive('banPath')
            ->with('/a', 'b', array('example.com'))
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidateRegex('/a', 'b', array('example.com'));
    }

    public function testInvalidateTags()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\BanInterface')
            ->shouldReceive('ban')
            ->with(array('X-Cache-Tags' => '(post\-1|posts)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidateTags(array('post-1', 'posts'));
    }

    public function testInvalidateTagsCustomHeader()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\ProxyClient\Invalidation\BanInterface')
            ->shouldReceive('ban')
            ->with(array('Custom-Tags' => '(post\-1)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->setTagsHeader('Custom-Tags');
        $cacheInvalidator->invalidateTags(array('post-1'));
    }

    public function testMethodException()
    {
        $proxy = \Mockery::mock('\FOS\HttpCache\ProxyClient\ProxyClientInterface');
        $cacheInvalidator = new CacheInvalidator($proxy);
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
            $cacheInvalidator->invalidate(array());
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
            $cacheInvalidator->invalidateTags(array());
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
        $unreachableException = ProxyUnreachableException::proxyUnreachable('http://127.0.0.1', 'Couldn\'t connect to host');
        $responseException    = ProxyResponseException::proxyResponse('http://127.0.0.1', 403, 'Forbidden');

        $exceptions = new ExceptionCollection();
        $exceptions->add($unreachableException)->add($responseException);

        $httpCache = \Mockery::mock('\FOS\HttpCache\ProxyClient\ProxyClientInterface')
            ->shouldReceive('flush')->once()->andThrow($exceptions)
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($httpCache);

        $logger = \Mockery::mock('\Psr\Log\LoggerInterface')
            ->shouldReceive('log')->once()
            ->with(
                'critical',
                'Request to caching proxy at http://127.0.0.1 failed with message "Couldn\'t connect to host"',
                array(
                    'exception' => $unreachableException
                )
            )
            ->shouldReceive('log')->once()
            ->with('critical', '403 error response "Forbidden" from caching proxy at http://127.0.0.1', array('exception' => $responseException))
            ->getMock();

        $cacheInvalidator->addSubscriber(new LogSubscriber($logger));

        $cacheInvalidator
            ->flush()
        ;
    }
}
