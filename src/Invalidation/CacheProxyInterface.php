<?php

namespace FOS\HttpCache\Invalidation;

/**
 * An HTTP caching reverse proxy
 *
 */
interface CacheProxyInterface
{
    /**
     * Send all pending invalidation requests.
     *
     * @return $this
     */
    public function flush();
}
