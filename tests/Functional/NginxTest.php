<?php

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\Invalidation\Nginx;
use FOS\HttpCache\Tests\NginxTestCase;

/**
 * @group webserver
 * @group nginx
 */
class NginxTest extends NginxTestCase
{
    public function testPurge()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->nginx->purge('http://localhost:6183/cache.php')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeSeparateLocation()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));
        
        $this->nginx = new Nginx(
            array('http://127.0.0.1:' . $this->getCachingProxyPort()),
            $this->getHostName() . ':' . $this->getCachingProxyPort(),
            '/purge'
        );
        $this->nginx->purge('http://localhost:6183/cache.php')->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $response = $this->getResponse('/cache.php');
        $this->assertHit($response);

        $this->nginx->refresh('http://localhost:6183/cache.php')->flush();
        usleep(1000);
        $refreshed = $this->getResponse('/cache.php');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }
}
