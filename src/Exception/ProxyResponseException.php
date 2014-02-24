<?php

namespace FOS\HttpCache\Exception;

/**
 * An error response from the caching proxy
 */
class ProxyResponseException extends \RuntimeException
{
    public function __construct($proxy, $statusCode, $statusMessage, \Exception $previous = null)
    {
        parent::__construct(
            sprintf(
                '%s error response "%s" from caching proxy at %s',
                $statusCode,
                $statusMessage,
                $proxy
            ),
            $statusCode,
            $previous
        );
    }
} 