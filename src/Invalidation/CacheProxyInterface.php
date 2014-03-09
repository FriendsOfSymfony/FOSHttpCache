<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\ExceptionCollection;

/**
 * An HTTP caching reverse proxy.
 *
 * Implementations should implement at least one of the Method interfaces.
 */
interface CacheProxyInterface
{
    /**
     * Send all pending invalidation requests.
     *
     * @return $this
     *
     * @throws ExceptionCollection If any errors occurred during flush
     */
    public function flush();
}
