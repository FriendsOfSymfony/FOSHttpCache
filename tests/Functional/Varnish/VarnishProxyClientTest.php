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

use FOS\HttpCache\ProxyClient\Varnish;
use FOS\HttpCache\Test\VarnishTestCase;
use FOS\HttpCache\Tests\Functional\Fixtures\BanTest;

/**
 * @group webserver
 * @group varnish
 */
class VarnishProxyClientTest extends VarnishTestCase
{
    use BanTest;

    public function testPurge()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getProxyClient()->purge('/cache.php')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeContentType()
    {
        $json = ['Accept' => 'application/json'];
        $html = ['Accept' => 'text/html'];

        $response = $this->getResponse('/negotation.php', $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertHit($this->getResponse('/negotation.php', $json));

        $response = $this->getResponse('/negotation.php', $html);
        $this->assertContains('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertMiss($response);
        $this->assertHit($this->getResponse('/negotation.php', $html));

        $this->getResponse('/negotation.php');
        $this->getProxyClient()->purge('/negotation.php')->flush();
        $this->assertMiss($this->getResponse('/negotation.php', $json));
        $this->assertMiss($this->getResponse('/negotation.php', $html));
    }

    public function testPurgeHost()
    {
        $varnish = new Varnish(['http://127.0.0.1:' . $this->getProxy()->getPort()]);

        $this->getResponse('/cache.php');

        $varnish->purge('http://localhost:6181/cache.php')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $response = $this->getResponse('/cache.php');
        $this->assertHit($response);

        $this->getProxyClient()->refresh('/cache.php')->flush();
        usleep(1000);
        $refreshed = $this->getResponse('/cache.php');

        $originalTimestamp = (float)(string) $response->getBody();
        $refreshedTimestamp = (float)(string) $refreshed->getBody();

        $this->assertGreaterThan($originalTimestamp, $refreshedTimestamp);
    }

    public function testRefreshContentType()
    {
        $json = ['Accept' => 'application/json'];
        $html = ['Accept' => 'text/html'];

        $this->getProxyClient()->refresh('/negotation.php', $json)->flush();

        $this->assertHit($this->getResponse('/negotation.php', $json));
        $this->assertMiss($this->getResponse('/negotation.php', $html));
    }
}
