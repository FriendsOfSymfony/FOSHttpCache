<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\ExceptionCollection;

/**
 * An HTTP caching reverse proxy.
 */
interface CacheProxyInterface
{
    /**
     * Send all pending invalidation requests.
     *
     * @return int The number of cache invalidations performed per caching server.
     *
     * @throws ExceptionCollection If any errors occurred during flush.
     */
    public function flush();
}
