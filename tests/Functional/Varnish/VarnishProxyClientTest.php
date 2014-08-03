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

/**
 * @group webserver
 * @group varnish
 */
class VarnishProxyClientTest extends VarnishTestCase
{
    public function testBanAll()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->assertMiss($this->getResponse('/json.php'));
        $this->assertHit($this->getResponse('/json.php'));

        $this->getProxyClient()->ban(array(Varnish::HTTP_HEADER_URL => '.*'))->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertMiss($this->getResponse('/json.php'));
    }

    public function testBanHost()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getProxyClient()->ban(array(Varnish::HTTP_HEADER_HOST => 'wrong-host.lo'))->flush();
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getProxyClient()->ban(array(Varnish::HTTP_HEADER_HOST => $this->getHostname()))->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testBanPathAll()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->assertMiss($this->getResponse('/json.php'));
        $this->assertHit($this->getResponse('/json.php'));

        $this->getProxyClient()->banPath('.*')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertMiss($this->getResponse('/json.php'));
    }

    public function testBanPathContentType()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->assertMiss($this->getResponse('/json.php'));
        $this->assertHit($this->getResponse('/json.php'));

        $this->getProxyClient()->banPath('.*', 'text/html')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/json.php'));
    }

    public function testPurge()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getProxyClient()->purge('/cache.php')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $response = $this->getResponse('/negotation.php', $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getContentType());
        $this->assertHit($this->getResponse('/negotation.php', $json));

        $response = $this->getResponse('/negotation.php', $html);
        $this->assertContains('text/html', $response->getContentType());
        $this->assertMiss($response);
        $this->assertHit($this->getResponse('/negotation.php', $html));

        $this->getResponse('/negotation.php');
        $this->getProxyClient()->purge('/negotation.php')->flush();
        $this->assertMiss($this->getResponse('/negotation.php', $json));
        $this->assertMiss($this->getResponse('/negotation.php', $html));
    }

    public function testPurgeHost()
    {
        $varnish = new Varnish(array('http://127.0.0.1:' . $this->getProxy()->getPort()));

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
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }

    public function testRefreshContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $this->getProxyClient()->refresh('/negotation.php', $json)->flush();

        $this->assertHit($this->getResponse('/negotation.php', $json));
        $this->assertMiss($this->getResponse('/negotation.php', $html));
    }
}
