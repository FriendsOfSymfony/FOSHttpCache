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
     *
     * @throws \FOS\HttpCache\Exception\ExceptionCollection If any errors occurred
     *                                                      during flush
     */
    public function flush();
}
