<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\ExceptionCollection;

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
     * @throws ExceptionCollection If any errors occurred during flush
     */
    public function flush();
}
