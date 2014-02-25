<?php

namespace FOS\HttpCache\Exception;

class MissingHostException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $path Path
     */
    public function __construct($path)
    {
        $msg = sprintf(
            'Path "%s" cannot be invalidated without a host. '
            . 'Either invalidate full URLs containing hostnames instead of paths '
            . 'or configure the the caching proxy class with a hostname.',
            $path
        );

        parent::__construct($msg);
    }
}
