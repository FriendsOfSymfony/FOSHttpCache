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
 * Wrapping an error response from the caching proxy.
 */
class ProxyResponseException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param string     $host          The host name that was contacted.
     * @param string     $statusCode    The status code of the reply.
     * @param string     $statusMessage The error message.
     * @param string     $details       Further details about the request that caused the error.
     * @param \Exception $previous      The exception from the HTTP client.
     *
     * @return ProxyUnreachableException
     */
    public static function proxyResponse($host, $statusCode, $statusMessage, $details = '', \Exception $previous = null)
    {
        $message = sprintf(
            '%s error response "%s" from caching proxy at %s',
            $statusCode,
            $statusMessage,
            $host
        );
        if ($details) {
            $message .= ". $details";
        }

        return new ProxyResponseException($message, $statusCode, $previous);
    }
}
