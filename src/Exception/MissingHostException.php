<?php

namespace FOS\HttpCache\Exception;

class MissingHostException extends \RuntimeException
{
    public function __construct($path)
    {
        $msg = sprintf(
            'Path "%s" cannot be invalidated without a host. '
            . 'Either invalidate full URLs containing hostnames instead of paths '
            . 'or configure the cache invalidator with a hostname.',
            $path
        );

        parent::__construct($msg);
    }
} 