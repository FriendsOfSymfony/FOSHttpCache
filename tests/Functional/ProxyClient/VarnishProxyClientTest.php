<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\ProxyClient;

use FOS\HttpCache\ProxyClient\Varnish;
use FOS\HttpCache\Test\VarnishTestCase;

/**
 * @group webserver
 * @group varnish
 */
class VarnishProxyClientTest extends VarnishTestCase
{
    use RefreshAssertions;
    use PurgeAssertions;
    use BanAssertions;

    public function testBanAll()
    {
        $this->assertBanAll($this->getProxyClient(), Varnish::HTTP_HEADER_URL);
    }

    public function testBanHost()
    {
        $this->assertBanHost($this->getProxyClient(), Varnish::HTTP_HEADER_HOST, $this->getHostName());
    }

    public function testBanPathAll()
    {
        $this->assertBanPath($this->getProxyClient());
    }

    public function testBanPathContentType()
    {
        $this->assertBanPathContentType($this->getProxyClient());
    }

    public function testPurge()
    {
        $this->assertPurge($this->getProxyClient());
    }

    public function testPurgeContentType()
    {
        $this->assertPurgeContentType($this->getProxyClient());
    }

    public function testPurgeHost()
    {
        $this->assertPurgeHost($this->getProxyClient(), 'http://localhost:6181');
    }

    public function testRefresh()
    {
        $this->assertRefresh($this->getProxyClient());
    }

    public function testRefreshContentType()
    {
        $this->assertRefreshContentType($this->getProxyClient());
    }
}
