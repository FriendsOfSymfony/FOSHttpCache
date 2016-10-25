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

/**
 * @group webserver
 * @group nginx
 */
class NginxProxyClientTest extends NginxTestCase
{
    use RefreshAssertions;
    use PurgeAssertions;

    public function testPurgeSeparateLocation()
    {
        $this->assertPurge($this->getProxyClient('/purge'));
    }

    public function testPurgeSameLocation()
    {
        $this->assertPurge($this->getProxyClient());
    }

    public function testPurgeContentType()
    {
        $this->markTestSkipped('Not working with nginx, it can only purge one type');

        $this->assertPurgeContentType($this->getProxyClient());
    }

    public function testPurgeSeparateLocationHost()
    {
        $this->assertPurgeHost($this->getProxyClient('/purge'), sprintf('http://%s', $this->getHostName()));
    }

    public function testPurgeSameLocationHost()
    {
        $this->assertPurgeHost($this->getProxyClient(), sprintf('http://%s', $this->getHostName()));
    }

    public function testRefresh()
    {
        $this->assertRefresh($this->getProxyClient());
    }

    public function testRefreshContentType()
    {
        $this->markTestSkipped('TODO: is nginx mixing up variants?');

        $this->assertRefreshContentType($this->getProxyClient());
    }
}
