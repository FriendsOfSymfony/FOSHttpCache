<?php

namespace FOS\HttpCache\Exception;

class InvalidUrlException extends \InvalidArgumentException
{
    /**
     * Constructor
     *
     * @param string $url    Invalid URL
     * @param string $reason Reason (optional)
     */
    public function __construct($url, $reason = null)
    {
        $msg = sprintf('URL "%s" is invalid.', $url);
        if ($reason) {
            $msg .= sprintf('Reason: %s', $reason);
        }

        parent::__construct($msg);
    }
} 