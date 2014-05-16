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
     * @param string     $statusMessage The content of the reply.
     * @param \Exception $previous      The exception from guzzle.
     *
     * @return ProxyUnreachableException
     */
    public static function proxyResponse($host, $statusCode, $statusMessage, \Exception $previous = null)
    {
        return new ProxyResponseException(
            sprintf(
                '%s error response "%s" from caching proxy at %s',
                $statusCode,
                $statusMessage,
                $host
            ),
            $statusCode,
            $previous
        );
    }
}
