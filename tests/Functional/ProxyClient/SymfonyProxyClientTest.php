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

use FOS\HttpCache\Test\SymfonyTestCase;

/**
 * @group webserver
 * @group symfony
 */
class SymfonyProxyClientTest extends SymfonyTestCase
{
    use RefreshAssertions;
    use PurgeAssertions;
    use InvalidateTagsAssertions;

    public function testPurge()
    {
        $this->assertPurge($this->getProxyClient(), '/symfony.php/cache');
    }

    public function testPurgeContentType()
    {
        $this->assertPurge($this->getProxyClient(), '/symfony.php/negotiation');
    }

    public function testPurgeHost()
    {
        $this->assertPurgeHost($this->getProxyClient(), 'http://localhost:8080', '/symfony.php/cache');
    }

    public function testRefresh()
    {
        $this->assertRefresh($this->getProxyClient(), '/symfony.php/cache');
    }

    public function testRefreshContentType()
    {
        $this->assertRefresh($this->getProxyClient(), '/symfony.php/negotiation');
    }

    public function testInvalidateTags()
    {
        $this->assertInvalidateTags($this->getProxyClient(), ['tag1'], '/symfony.php/tags');
    }

    public function testInvalidateTagsMultiHeader()
    {
        $this->assertInvalidateTags($this->getProxyClient(), ['tag2'], '/symfony.php/tags_multi_header');
    }
}
