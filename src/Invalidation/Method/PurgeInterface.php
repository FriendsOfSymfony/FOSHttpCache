<?php

namespace FOS\HttpCache\Invalidation\Method;

use FOS\HttpCache\Invalidation\CacheProxyInterface;

/**
 * An HTTP cache that supports invalidation by purging, that is, removing one
 * URL from the cache.
 *
 * Implementations should be configurable with a default host to be able to
 * handle purge calls that do not contain a full URL but only a path.
 */
interface PurgeInterface extends CacheProxyInterface
{
    /**
     * Purge a URL
     *
     * Purging a URL will remove the cache for the URL, including the query
     * string, with all its Vary variants.
     *
     * If the $url is just a path, the cache proxy class will add a default
     * host name.
     *
     * @param string $url Path or URL to purge.
     *
     * @return $this
     */
    public function purge($url);
}
