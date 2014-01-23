<?php

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\CacheManager;
use FOS\HttpCache\Test\VarnishTestCase;

class CacheManagerTest extends VarnishTestCase
{
    public function testInvalidateTags()
    {
        $cacheManager = new CacheManager($this->varnish);

        $this->assertMiss(self::getResponse('/tags.php'));
        $this->assertHit(self::getResponse('/tags.php'));

        $cacheManager->invalidateTags(array('tag1'))->flush();

        $this->assertMiss(self::getResponse('/tags.php'));
    }
}
