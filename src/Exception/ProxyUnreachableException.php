<?php

namespace FOS\HttpCache\Exception;

/**
 * Thrown when a request to the reverse caching proxy fails
 */
class ProxyUnreachableException extends \RuntimeException
{
    public function __construct($host, $message, \Exception $previous = null)
    {
        parent::__construct(
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