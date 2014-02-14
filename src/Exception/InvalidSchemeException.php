<?php

namespace FOS\HttpCache\Exception;

class InvalidSchemeException extends \InvalidArgumentException
{
    public function __construct($host, $scheme, $expected)
    {
        parent::__construct(sprintf(
            'Host "%s" with scheme "%s" is invalid. Only scheme "%s" is supported',
            $host,
            $scheme,
            $expected
        ));
    }
}