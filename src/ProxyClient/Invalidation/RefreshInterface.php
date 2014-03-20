<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient\Invalidation;

use FOS\HttpCache\ProxyClient\ProxyClientInterface;

/**
 * An HTTP cache that supports invalidation by refresh requests that force a
 * cache miss for one specific URL.
 *
 * Implementations should be configurable with a default host to be able to
 * handle refresh calls that do not contain a full URL but only a path.
 */
interface RefreshInterface extends ProxyClientInterface
{
    /**
     * Refresh a URL.
     *
     * Refreshing a URL will generate a new cached response for the URL,
     * including the query string but excluding any Vary variants.
     *
     * If the $url is just a path, the proxy client class will add a default
     * host name.
     *
     * @param string $url     Path or URL to refresh.
     * @param array  $headers Extra HTTP headers to send to the caching proxy
     *                        (optional)
     *
     * @return $this
     */
    public function refresh($url, array $headers = array());
}
