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

use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;

/**
 * Assertions that do the purge operations.
 */
trait PurgeAssertions
{
    /**
     * Asserting that purging leads to invalidated content.
     *
     * @param PurgeCapable $proxyClient The client to send purge instructions to the cache
     * @param string       $path        The path to get and purge, defaults to /cache.php
     */
    protected function assertPurge(PurgeCapable $proxyClient, $path = '/cache.php')
    {
        $this->assertMiss($this->getResponse($path));
        $this->assertHit($this->getResponse($path));

        $proxyClient->purge($path)->flush();
        $this->assertMiss($this->getResponse($path));
    }

    /**
     * Asserting that purging including the domain leads to invalidated content.
     *
     * @param PurgeCapable $proxyClient The client to send purge instructions to the cache
     * @param string       $host        The host name to use in the purge request
     * @param string       $path        The path to get and purge, defaults to /cache.php
     */
    protected function assertPurgeHost(PurgeCapable $proxyClient, $host, $path = '/cache.php')
    {
        $this->assertMiss($this->getResponse($path));
        $this->assertHit($this->getResponse($path));

        $proxyClient->purge($host.$path)->flush();
        $this->assertMiss($this->getResponse($path));
    }

    protected function assertPurgeContentType(PurgeCapable $proxyClient, $path = '/negotiation.php')
    {
        $json = ['Accept' => 'application/json'];
        $html = ['Accept' => 'text/html'];

        $response = $this->getResponse($path, $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertHit($this->getResponse($path, $json));

        $response = $this->getResponse($path, $html);
        $this->assertContains('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertMiss($response);
        $this->assertHit($this->getResponse($path, $html));

        $this->getResponse($path);
        $proxyClient->purge($path)->flush();
        $this->assertMiss($this->getResponse($path, $json));
        $this->assertMiss($this->getResponse($path, $html));
    }
}
