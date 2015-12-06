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
use FOS\HttpCache\CacheInvalidator;

/**
 * @group webserver
 * @group symfony
 */
class SymfonyProxyClientTest extends SymfonyTestCase
{
    const CACHE_URL = '/symfony.php/cache';

    public function testPurge()
    {
        $this->assertMiss($this->getResponse(self::CACHE_URL));
        $this->assertHit($this->getResponse(self::CACHE_URL));

        $this->getProxyClient()->purge(self::CACHE_URL)->flush();
        $this->assertMiss($this->getResponse(self::CACHE_URL));
    }

    public function testInvalidateTags()
    {
        $client = $this->getProxyClient([
            'tags_invalidator' => new SymlinkManager(__DIR__ . '/Tag/cache
        ]);
        $cacheInvalidator = new CacheInvalidator($client);
        $resp = $this->getResponse(self::CACHE_URL, [
            $client->getTagsHeaderName() => $client->getTagsHeaderValue(['tag1'])
        ]);
        $this->assertMiss($resp);
        $this->assertHit($this->getResponse(self::CACHE_URL));

        $cacheInvalidator->invalidateTags(['tag1']);
        $cacheInvalidator->flush();

        $this->assertMiss($this->getResponse(self::CACHE_URL));
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

        $this->getResponse(self::CACHE_URL);

        $symfony->purge('http://localhost:8080/symfony.php/cache')->flush();
        $this->assertMiss($this->getResponse(self::CACHE_URL));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse(self::CACHE_URL));
        $response = $this->getResponse(self::CACHE_URL);
        $this->assertHit($response);

        $this->getProxyClient()->refresh(self::CACHE_URL)->flush();
        usleep(100);
        $refreshed = $this->getResponse(self::CACHE_URL);

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
