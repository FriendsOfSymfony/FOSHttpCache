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

    /**
     * @dataProvider fileProvider
     */
    public function testPurgeSeparateLocation($file)
    {
        $this->assertMiss($this->getResponse($file));
        $this->assertHit($this->getResponse($file));
        
        $this->nginx = new Nginx(
            array('http://127.0.0.1:' . $this->getCachingProxyPort()),
            $this->getHostName() . ':' . $this->getCachingProxyPort(),
            '/purge'
        );
        $this->nginx->purge('http://localhost:6183/'.$file)->flush();

        $this->assertMiss($this->getResponse($file));
    }

    /**
     * @dataProvider fileProvider
     */
    public function testExpired($file)
    {
        $this->assertMiss($this->getResponse($file));
        $this->assertHit($this->getResponse($file));

        sleep(12);

        $this->assertExpired($this->getResponse($file));
    }
    
    /**
     * @dataProvider fileProvider
     */
    public function testRefresh($file)
    {
        $this->assertMiss($this->getResponse($file));
        $response = $this->getResponse($file);
        $this->assertHit($response);

        $this->nginx->refresh('http://localhost:6183/'.$file)->flush();
        usleep(1000);
        $refreshed = $this->getResponse($file);
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }
    
    public function fileProvider()
    {
        return array(
          array("/cache_cache-control.php"),
          array("/cache_expires.php"),
          array("/cache_x-accel-expires.php")
        );
    }

}
