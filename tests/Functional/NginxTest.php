<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\Test\NginxTestCase;

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

        $nginx = $this->getProxyClient('/purge');

        $this->markTestSkipped('This does not work yet - Nginx client has to insert the separate location path.');
        $nginx->purge(sprintf('http://%s/cache.php', $this->getHostName()))->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeSeparateLocationPath()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $nginx = $this->getProxyClient('/purge');
        $nginx->purge('/cache.php')->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeSameLocation()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $nginx = $this->getProxyClient();
        $nginx->purge(sprintf('http://%s/cache.php', $this->getHostName()))->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeSameLocationPath()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $nginx = $this->getProxyClient();
        $nginx->purge('/cache.php')->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $response = $this->getResponse('/cache.php');
        $this->assertHit($response);

        $nginx = $this->getProxyClient();
        $nginx->refresh('/cache.php')->flush();
        usleep(1000);
        $refreshed = $this->getResponse('/cache.php');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }

    public function testRefreshPath()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $response = $this->getResponse('/cache.php');
        $this->assertHit($response);

        $nginx = $this->getProxyClient();
        $nginx->refresh('/cache.php')->flush();
        usleep(1000);
        $refreshed = $this->getResponse('/cache.php');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }
}
