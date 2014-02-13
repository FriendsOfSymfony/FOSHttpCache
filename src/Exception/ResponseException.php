<?php

namespace FOS\HttpCache\Exception;

/**
 * An error response from the caching proxy
 */
class ResponseException extends \RuntimeException
{
    public function __construct($statusCode, $statusMessage, \Exception $previous = null)
    {
        parent::__construct($statusMessage, $statusCode, $previous);
    }
} 