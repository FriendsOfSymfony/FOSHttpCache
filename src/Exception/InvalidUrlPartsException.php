<?php

namespace FOS\HttpCache\Exception;

class InvalidUrlPartsException extends InvalidUrlException
{
    /**
     * Constructor
     *
     * @param string $server  Invalid server
     * @param array  $allowed Allowed URL parts
     */
    public function __construct($server, array $allowed)
    {
        parent::__construct(sprintf(
            'Server "%s" is invalid. Only %s URL parts are allowed.',
            $server,
            implode(', ', $allowed)
        ));
    }
} 