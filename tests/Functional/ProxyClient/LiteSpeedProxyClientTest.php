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
    use PurgeAssertions;

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
}
