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

use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;

/**
 * Assertions that do the ban operations.
 */
trait BanAssertions
{
    /**
     * Asserting that banning everything leads to all content getting invalidated.
     *
     * @param BanCapable $proxyClient The client to send ban instructions to the cache
     * @param string     $header      The header that holds the URLs
     * @param array      $paths       The paths to get, defaults to [/cache.php, json.php]
     */
    protected function assertBanAll(BanCapable $proxyClient, $header, array $paths = ['/cache.php', '/json.php'])
    {
        foreach ($paths as $path) {
            $this->assertMiss($this->getResponse($path));
            $this->assertHit($this->getResponse($path));
        }

        $proxyClient->ban([$header => '.*'])->flush();

        foreach ($paths as $path) {
            $this->assertMiss($this->getResponse($path));
        }
    }

    /**
     * Asserting that only banning the right host leads to content getting invalidated.
     *
     * @param BanCapable $proxyClient The client to send ban instructions to the cache
     * @param string     $header      The header that holds the URLs
     * @param string     $hostname    Name of the host so we can invalidate that host
     * @param string     $path        The path to get, defaults to /cache.php
     */
    protected function assertBanHost(BanCapable $proxyClient, $header, $hostname, $path = '/cache.php')
    {
        $this->assertMiss($this->getResponse($path));
        $this->assertHit($this->getResponse($path));

        $proxyClient->ban([$header => 'wrong-host.lo'])->flush();
        $this->assertHit($this->getResponse($path));

        $proxyClient->ban([$header => $hostname])->flush();
        $this->assertMiss($this->getResponse($path));
    }

    /**
     * Asserting that banPath leads to content getting invalidated.
     *
     * @param BanCapable $proxyClient The client to send ban instructions to the cache
     * @param array      $paths       The paths to get, defaults to [/cache.php, json.php]
     */
    protected function assertBanPath(BanCapable $proxyClient, array $paths = ['/cache.php', '/json.php'])
    {
        foreach ($paths as $path) {
            $this->assertMiss($this->getResponse($path));
            $this->assertHit($this->getResponse($path));
        }

        $proxyClient->banPath('.*')->flush();

        foreach ($paths as $path) {
            $this->assertMiss($this->getResponse($path));
        }
    }

    /**
     * Asserting that banPath leads to content getting invalidated.
     *
     * @param BanCapable $proxyClient The client to send ban instructions to the cache
     * @param string     $htmlPath    Path to a HTML content, defaults to /cache.php
     * @param string     $otherPath   Path to a non-HTML content, defaults to json.php
     */
    protected function assertBanPathContentType(BanCapable $proxyClient, $htmlPath = '/cache.php', $otherPath = '/json.php')
    {
        $this->assertMiss($this->getResponse($htmlPath));
        $this->assertHit($this->getResponse($htmlPath));

        $this->assertMiss($this->getResponse($otherPath));
        $this->assertHit($this->getResponse($otherPath));

        $proxyClient->banPath('.*', 'text/html')->flush();
        $this->assertMiss($this->getResponse($htmlPath));
        $this->assertHit($this->getResponse($otherPath));
    }
}
