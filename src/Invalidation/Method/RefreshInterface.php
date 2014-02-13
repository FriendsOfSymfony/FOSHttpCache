<?php

namespace FOS\HttpCache\Invalidation\Method;

use FOS\HttpCache\Invalidation\CacheProxyInterface;

/**
 * An HTTP cache that supports invalidation by refresh requests that force a
 * cache miss for one specific URL
 *
 */
interface RefreshInterface extends CacheProxyInterface
{
    /**
     * Refresh a URL.
     *
     * Refreshing a URL will generate a new cached response for the URL,
     * including the query string but excluding any Vary variants.
     *
     * @param string $url     Path or URL to refresh.
     * @param array  $headers Extra HTTP headers to send to the caching proxy
     *                        (optional)
     *
     * @return $this
     */
    public function refresh($url, array $headers = array());
}
