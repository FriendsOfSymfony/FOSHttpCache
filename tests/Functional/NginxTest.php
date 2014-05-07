<?php

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\ProxyClient\Nginx;
use FOS\HttpCache\Tests\NginxTestCase;

/**
 * @group webserver
 * @group nginx
 */
class NginxTest extends NginxTestCase
{
    public function testPurgeSeparateLocation()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $nginx = $this->getNginx('/purge');

        $nginx->purge($this->getHostname() . '/cache.php')->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeSeparateLocationPath()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $nginx = $this->getNginx('/purge');
        $nginx->purge('/cache.php')->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeSameLocation()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $nginx = $this->getNginx();
        $nginx->purge($this->getHostname() . '/cache.php')->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeSameLocationPath()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $nginx = $this->getNginx();
        $nginx->purge('/cache.php')->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $response = $this->getResponse('/cache.php');
        $this->assertHit($response);

        $nginx = $this->getNginx();
        $nginx->refresh($this->getHostname() . '/cache.php')->flush();
        usleep(1000);
        $refreshed = $this->getResponse('/cache.php');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }

    public function testRefreshPath()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $response = $this->getResponse('/cache.php');
        $this->assertHit($response);

        $nginx = $this->getNginx();
        $nginx->refresh('/cache.php')->flush();
        usleep(1000);
        $refreshed = $this->getResponse('/cache.php');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }
}
