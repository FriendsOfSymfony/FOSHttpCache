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

use FOS\HttpCache\Test\NginxTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('webserver')]
#[Group('nginx')]
class NginxProxyClientTest extends NginxTestCase
{
    use PurgeAssertions;
    use RefreshAssertions;

    public function testPurgeSeparateLocation(): void
    {
        $this->assertPurge($this->getProxyClient('/purge'));
    }

    public function testPurgeSameLocation(): void
    {
        $this->assertPurge($this->getProxyClient());
    }

    public function testPurgeContentType(): void
    {
        $this->markTestSkipped('Not working with nginx, it can only purge one type');

        $this->assertPurgeContentType($this->getProxyClient());
    }

    public function testPurgeSeparateLocationHost(): void
    {
        $this->assertPurgeHost($this->getProxyClient('/purge'), sprintf('http://%s', $this->getHostName()));
    }

    public function testPurgeSameLocationHost(): void
    {
        $this->assertPurgeHost($this->getProxyClient(), sprintf('http://%s', $this->getHostName()));
    }

    public function testRefresh(): void
    {
        $this->assertRefresh($this->getProxyClient());
    }

    public function testRefreshContentType(): void
    {
        $this->markTestSkipped('TODO: is nginx mixing up variants?');

        $this->assertRefreshContentType($this->getProxyClient());
    }
}
