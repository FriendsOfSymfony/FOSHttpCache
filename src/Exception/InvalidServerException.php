<?php

namespace FOS\HttpCache\Exception;

class InvalidServerException extends \InvalidArgumentException
{
    public function __construct($server)
    {
        parent::__construct(sprintf(
            'Server "%s" is invalid. Only scheme, host and port are allowed',
            $server
        ));
    }
} 