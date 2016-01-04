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

use FOS\HttpCache\ProxyClient\Symfony;
use FOS\HttpCache\Test\SymfonyTestCase;

/**
 * @group webserver
 * @group symfony
 */
class SymfonyProxyClientTest extends SymfonyTestCase
{
    public function testPurge()
    {
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
        $this->assertHit($this->getResponse('/symfony.php/cache'));

        $this->getProxyClient()->purge('/symfony.php/cache')->flush();
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
    }

    public function testPurgeContentType()
    {
        $json = ['Accept' => 'application/json'];
        $html = ['Accept' => 'text/html'];

        $response = $this->getResponse('/symfony.php/negotiation', $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertHit($this->getResponse('/symfony.php/negotiation', $json));

        $response = $this->getResponse('/symfony.php/negotiation', $html);
        $this->assertContains('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertMiss($response);
        $this->assertHit($this->getResponse('/symfony.php/negotiation', $html));

        $this->getResponse('/symfony.php/negotiation');
        $this->getProxyClient()->purge('/symfony.php/negotiation')->flush();
        $this->assertMiss($this->getResponse('/symfony.php/negotiation', $json));
        $this->assertMiss($this->getResponse('/symfony.php/negotiation', $html));
    }

    public function testPurgeHost()
    {
        $symfony = new Symfony(['http://127.0.0.1:' . $this->getCachingProxyPort()], ['purge_method' => 'NOTIFY']);

        $this->getResponse('/symfony.php/cache');

        $symfony->purge('http://localhost:8080/symfony.php/cache')->flush();
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
        $response = $this->getResponse('/symfony.php/cache');
        $this->assertHit($response);

        $this->getProxyClient()->refresh('/symfony.php/cache')->flush();
        usleep(100);
        $refreshed = $this->getResponse('/symfony.php/cache');

        $originalTimestamp = (float)(string) $response->getBody();
        $refreshedTimestamp = (float)(string) $refreshed->getBody();

        $this->assertGreaterThan($originalTimestamp, $refreshedTimestamp);
    }

    public function testRefreshContentType()
    {
        $json = ['Accept' => 'application/json'];
        $html = ['Accept' => 'text/html'];

        $this->getProxyClient()->refresh('/symfony.php/negotiation', $json)->flush();

        $this->assertHit($this->getResponse('/symfony.php/negotiation', $json));
        $this->assertMiss($this->getResponse('/symfony.php/negotiation', $html));
    }
}
