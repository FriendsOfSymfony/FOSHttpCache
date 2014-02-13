<?php

namespace FOS\HttpCache\Tests\Unit;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\Exception\UnsupportedInvalidationMethodException;
use \Mockery;

class CacheInvalidatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->cacheProxy = \Mockery::mock('\FOS\HttpCache\Invalidation\CacheProxyInterface');
    }

    public function testInvalidatePath()
    {
        $httpCache = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\PurgeInterface')
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
        $httpCache = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\RefreshInterface')
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

        $ban = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\BanInterface')
            ->shouldReceive('ban')
            ->with($headers)
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidate($headers);
    }

    public function testInvalidateRegex()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\BanInterface')
            ->shouldReceive('banPath')
            ->with('/a', 'b', array('example.com'))
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidateRegex('/a', 'b', array('example.com'));
    }

    public function testInvalidateTags()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\BanInterface')
            ->shouldReceive('ban')
            ->with(array('X-Cache-Tags' => '(post-1|posts)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->invalidateTags(array('post-1', 'posts'));
    }

    public function testInvalidateTagsCustomHeader()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\BanInterface')
            ->shouldReceive('ban')
            ->with(array('Custom-Tags' => '(post-1)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheInvalidator = new CacheInvalidator($ban);
        $cacheInvalidator->setTagsHeader('Custom-Tags');
        $cacheInvalidator->invalidateTags(array('post-1'));
    }

    public function testMethodException()
    {
        $proxy = \Mockery::mock('\FOS\HttpCache\Invalidation\CacheProxyInterface');
        $cacheInvalidator = new CacheInvalidator($proxy);
        try {
            $cacheInvalidator->invalidatePath('/');
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
        try {
            $cacheInvalidator->refreshPath('/');
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
        try {
            $cacheInvalidator->invalidate(array());
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
        try {
            $cacheInvalidator->invalidateRegex('/');
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
        try {
            $cacheInvalidator->invalidateTags(array());
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
    }
}
