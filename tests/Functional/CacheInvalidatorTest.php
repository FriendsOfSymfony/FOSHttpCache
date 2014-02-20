<?php

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\Tests\VarnishTestCase;

/**
 * @group webserver
 */
class CacheInvalidatorTest extends VarnishTestCase
{
    public function testInvalidateTags()
    {
        $cacheInvalidator = new CacheInvalidator($this->varnish);

        $this->assertMiss($this->getResponse('/tags.php'));
        $this->assertHit($this->getResponse('/tags.php'));

        $cacheInvalidator->invalidateTags(array('tag1'))->flush();

        $this->assertMiss($this->getResponse('/tags.php'));
    }
}
