<?php

namespace FOS\HttpCache\Exception;

class InvalidUrlSchemeException extends \InvalidArgumentException
{
    /**
     * Constructor
     *
     * @param string $host     HTTP host
     * @param string $scheme   HTTP scheme
     * @param string $expected Expected HTTP scheme
     */
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