<?php

namespace FOS\HttpCache\Exception;

/**
 * Thrown when a request to the reverse caching proxy fails to establish a
 * connection.
 */
class ProxyUnreachableException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param string     $host     The host name that was contacted.
     * @param string     $message  The error message from guzzle.
     * @param \Exception $previous The exception from guzzle.
     *
     * @return ProxyUnreachableException
     */
    public static function proxyUnreachable($host, $message, \Exception $previous = null)
    {
        return new ProxyUnreachableException(
            sprintf(
                'Request to caching proxy at %s failed with message "%s"',
                $host,
                $message
            ),
            0,
            $previous
        );
    }
}
