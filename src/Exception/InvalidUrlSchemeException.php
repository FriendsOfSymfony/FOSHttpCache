<?php

namespace FOS\HttpCache\Exception;

class InvalidUrlSchemeException extends \InvalidArgumentException
{
    /**
     * Constructor
     *
     * @param string $host    HTTP host
     * @param string $scheme  HTTP scheme
     * @param array  $allowed Expected HTTP schemes
     */
    public function __construct($host, $scheme, array $allowed)
    {
        parent::__construct(sprintf(
            'Host "%s" with scheme "%s" is invalid. Only schemes "%s" are supported',
            $host,
            $scheme,
            implode(', ', $allowed)
        ));
    }
}
