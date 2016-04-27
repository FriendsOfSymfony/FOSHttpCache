<?php

namespace FOS\HttpCache\Tests\Functional\Fixtures;

trait BanTest
{
    public function testBanAll()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->assertMiss($this->getResponse('/json.php'));
        $this->assertHit($this->getResponse('/json.php'));

        $this->getProxyClient()->ban(['X-Url' => '.*'])->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertMiss($this->getResponse('/json.php'));
    }

    public function testBanHost()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getProxyClient()->ban(['X-Host' => 'wrong-host.lo'])->flush();
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getProxyClient()->ban(['X-Host' => $this->getHostname()])->flush();
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
}
