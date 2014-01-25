<?php

namespace FOS\HttpCache\Tests;

use FOS\HttpCache\CacheManager;
use \Mockery;

class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheManager;

    public function setUp()
    {
        $this->cacheProxy = \Mockery::mock('\FOS\HttpCache\Invalidation\CacheProxyInterface');
    }

    public function testInvalidateRoute()
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
}
