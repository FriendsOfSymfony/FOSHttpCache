<?php

namespace FOS\HttpCache\Tests;

use FOS\HttpCache\CacheManager;
use FOS\HttpCache\Exception\UnsupportedInvalidationMethodException;
use \Mockery;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheManager;

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

        $cacheManager = new CacheManager($httpCache);

        $cacheManager
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

        $cacheManager = new CacheManager($httpCache);

        $cacheManager
            ->refreshPath('/my/route', $headers)
        ;
    }

    public function testInvalidateRegex()
    {
        $this->markTestIncomplete('TODO: implement feature');
    }

    public function testInvalidateTags()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\BanInterface')
            ->shouldReceive('ban')
            ->with(array('X-Cache-Tags' => '(post-1|posts)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheManager = new CacheManager($ban);
        $cacheManager->invalidateTags(array('post-1', 'posts'));
    }

    public function testInvalidateTagsCustomHeader()
    {
        $ban = \Mockery::mock('\FOS\HttpCache\Invalidation\Method\BanInterface')
            ->shouldReceive('ban')
            ->with(array('Custom-Tags' => '(post-1)(,.+)?$'))
            ->once()
            ->getMock();

        $cacheManager = new CacheManager($ban);
        $cacheManager->setTagsHeader('Custom-Tags');
        $cacheManager->invalidateTags(array('post-1'));
    }

    public function testMethodException()
    {
        $proxy = \Mockery::mock('\FOS\HttpCache\Invalidation\CacheProxyInterface');
        $cacheManager = new CacheManager($proxy);
        try {
            $cacheManager->invalidatePath('/');
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
        try {
            $cacheManager->refreshPath('/');
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
        /*
        try {
            $cacheManager->invalidateRegex('/');
            $this->fail('Expected exception');
        } catch (UnsupportedInvalidationMethodException $e) {
            // success
        }
         */
    }
}
