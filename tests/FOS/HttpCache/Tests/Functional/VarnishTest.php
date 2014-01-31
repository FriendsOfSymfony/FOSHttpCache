<?php

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\Invalidation\Varnish;
use FOS\HttpCache\Tests\VarnishTestCase;

class VarnishTest extends VarnishTestCase
{
    public function testBanAll()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->assertMiss(self::getResponse('/json.php'));
        $this->assertHit(self::getResponse('/json.php'));

        $this->varnish->ban(array(Varnish::HTTP_HEADER_URL => '.*'))->flush();

        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertMiss(self::getResponse('/json.php'));
    }

    public function testBanHost()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->varnish->ban(array(Varnish::HTTP_HEADER_HOST => 'wrong-host.lo'))->flush();
        $this->assertHit(self::getResponse('/cache.php'));

        $this->varnish->ban(array(Varnish::HTTP_HEADER_HOST => WEB_SERVER_HOSTNAME))->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
    }

    public function testBanPathAll()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->assertMiss(self::getResponse('/json.php'));
        $this->assertHit(self::getResponse('/json.php'));

        $this->varnish->banPath('.*')->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertMiss(self::getResponse('/json.php'));
    }

    public function testBanPathContentType()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->assertMiss(self::getResponse('/json.php'));
        $this->assertHit(self::getResponse('/json.php'));

        $this->varnish->banPath('.*', 'text/html')->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/json.php'));
    }

    public function testPurge()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $this->assertHit(self::getResponse('/cache.php'));

        $this->varnish->purge('/cache.php')->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
    }

    public function testPurgeContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $response = self::getResponse('/negotation.php', $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getContentType());
        $this->assertHit(self::getResponse('/negotation.php', $json));

        $response = self::getResponse('/negotation.php', $html);
        $this->assertEquals('text/html', $response->getContentType());
        $this->assertMiss($response);
        $this->assertHit(self::getResponse('/negotation.php', $html));

        $this->varnish->purge('/negotation.php')->flush();
        $this->assertMiss(self::getResponse('/negotation.php', $json));
        $this->assertMiss(self::getResponse('/negotation.php', $html));
    }

    public function testRefresh()
    {
        $this->assertMiss(self::getResponse('/cache.php'));
        $response = self::getResponse('/cache.php');
        $this->assertHit($response);

        $this->varnish->refresh('/cache.php')->flush();
        usleep(1000);
        $refreshed = self::getResponse('/cache.php');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }

    public function testRefreshContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $this->varnish->refresh('/negotation.php', $json)->flush();

        $this->assertHit(self::getResponse('/negotation.php', $json));
        $this->assertMiss(self::getResponse('/negotation.php', $html));
    }
}
