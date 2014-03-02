<?php

namespace FOS\HttpCache\Exception;

/**
 * Thrown when the CacheInvalidator is asked for an operation the underlying
 * cache proxy does not support.
 */
class UnsupportedInvalidationMethodException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param string $method Name of the HTTP method that would be required.
     *
     * @return UnsupportedInvalidationMethodException
     */
    public static function cacheDoesNotImplement($method)
    {
        return new self(sprintf('HTTP cache does not support %s requests', $method));
    }
}
