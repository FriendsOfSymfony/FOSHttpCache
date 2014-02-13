<?php

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\Tests\VarnishTestCase;

class CacheInvalidatorTest extends VarnishTestCase
{
    public function testInvalidateTags()
    {
        $cacheInvalidator = new CacheInvalidator($this->varnish);

        $this->assertMiss(self::getResponse('/tags.php'));
        $this->assertHit(self::getResponse('/tags.php'));

        $cacheInvalidator->invalidateTags(array('tag1'))->flush();

        $this->assertMiss(self::getResponse('/tags.php'));
    }
}
