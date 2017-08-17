<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Exception;

/**
 * Thrown when a request to the reverse caching proxy fails to establish a
 * connection.
 */
class ProxyUnreachableException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param string     $host     the host name that was contacted
     * @param string     $message  the error message from the HTTP client
     * @param string     $details  further details about the request that caused the error
     * @param \Exception $previous the exception from the HTTP client
     *
     * @return ProxyUnreachableException
     */
    public static function proxyUnreachable($host, $message, $details = '', \Exception $previous = null)
    {
        $message = sprintf(
            'Request to caching proxy at %s failed with message "%s"',
            $host,
            $message
        );
        if ($details) {
            $message .= ". $details";
        }

        return new self(
            $message,
            0,
            $previous
        );
    }
}
