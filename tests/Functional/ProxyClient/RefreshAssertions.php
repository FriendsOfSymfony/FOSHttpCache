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

use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;

/**
 * Assertions that do the refresh operations.
 */
trait RefreshAssertions
{
    /**
     * Asserting that refreshing leads to updated content that is already cached.
     *
     * @param RefreshCapable $proxyClient The client to send refresh instructions to the cache
     * @param string         $path        The path to get and refresh, defaults to /cache.php
     */
    protected function assertRefresh(RefreshCapable $proxyClient, $path = '/cache.php')
    {
        $this->assertMiss($this->getResponse($path));
        $response = $this->getResponse($path);
        $this->assertHit($response);

        $proxyClient->refresh($path)->flush();
        usleep(1000);
        $refreshed = $this->getResponse($path);

        $originalTimestamp = (float) (string) $response->getBody();
        $refreshedTimestamp = (float) (string) $refreshed->getBody();

        \PHPUnit_Framework_Assert::assertThat(
            $refreshedTimestamp,
            \PHPUnit_Framework_Assert::greaterThan($originalTimestamp)
        );
    }

    /**
     * Asserting that refreshing one variant does not touch the other variants.
     *
     * @param RefreshCapable $proxyClient The client to send refresh instructions to the cache
     * @param string         $path        The path to get and refresh, defaults to /negotiation.php
     */
    protected function assertRefreshContentType(RefreshCapable $proxyClient, $path = '/negotiation.php')
    {
        $json = ['Accept' => 'application/json'];
        $html = ['Accept' => 'text/html'];

        $proxyClient->refresh($path, $json)->flush();

        $this->assertHit($this->getResponse($path, $json));
        $this->assertMiss($this->getResponse($path, $html));
    }
}
