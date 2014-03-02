<?php

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\Invalidation\NginxSeparateLocation;
use FOS\HttpCache\Tests\NginxTestCase;

/**
 * @group webserver
 * @group nginx
 */
class NginxSeparateLocationTest extends NginxTestCase
{
    public function testPurge()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->nginx->purge('/cache.php')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

}
