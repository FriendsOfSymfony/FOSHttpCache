<?php

namespace FOS\HttpCache\Exception;

class UnsupportedInvalidationMethodException extends \RuntimeException
{
    public static function cacheDoesNotImplement($method)
    {
        return new self(sprintf('HTTP cache does not support %s requests', $method));
    }
}
