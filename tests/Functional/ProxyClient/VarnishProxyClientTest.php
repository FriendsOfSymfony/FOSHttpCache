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
use PHPUnit\Framework\Attributes\Group;

#[Group('webserver')]
#[Group('varnish')]
class VarnishProxyClientTest extends VarnishTestCase
{
    use BanAssertions;
    use PurgeAssertions;
    use RefreshAssertions;

    public function testBanAll(): void
    {
        $this->assertBanAll($this->getProxyClient(), Varnish::HTTP_HEADER_URL);
    }

    public function testBanHost(): void
    {
        $this->assertBanHost($this->getProxyClient(), Varnish::HTTP_HEADER_HOST, $this->getHostName());
    }

    public function testBanPathAll(): void
    {
        $this->assertBanPath($this->getProxyClient());
    }

    public function testBanPathContentType(): void
    {
        $this->assertBanPathContentType($this->getProxyClient());
    }

    public function testPurge(): void
    {
        $this->assertPurge($this->getProxyClient());
    }

    public function testPurgeContentType(): void
    {
        $this->assertPurgeContentType($this->getProxyClient());
    }

    public function testPurgeHost(): void
    {
        $this->assertPurgeHost($this->getProxyClient(), 'http://localhost:6181');
    }

    public function testRefresh(): void
    {
        $this->assertRefresh($this->getProxyClient());
    }

    public function testRefreshContentType(): void
    {
        $this->assertRefreshContentType($this->getProxyClient());
    }
}
