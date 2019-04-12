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

use FOS\HttpCache\Test\LiteSpeedTestCase;

/**
 * @group webserver
 * @group litespeed
 */
class LiteSpeedProxyClientTest extends LiteSpeedTestCase
{
    use RefreshAssertions;
    use PurgeAssertions;
    use InvalidateTagsAssertions;

    public function testPurge()
    {
        $this->assertPurge($this->getProxyClient());
    }

    public function testPurgeContentType()
    {
        $this->assertPurgeContentType($this->getProxyClient());
    }

    public function testRefresh()
    {
        $this->assertRefresh($this->getProxyClient());
    }

    public function testRefreshContentType()
    {
        $this->assertRefreshContentType($this->getProxyClient());
    }

    public function testInvalidateTags()
    {
        $this->assertInvalidateTags($this->getProxyClient(), ['tag1']);
    }
}
