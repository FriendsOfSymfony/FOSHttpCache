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

use Http\Client\Exception\NetworkException;

/**
 * Thrown when a request to the reverse caching proxy fails to establish a
 * connection.
 */
class ProxyUnreachableException extends \RuntimeException implements HttpCacheException
{
    /**
     * @return ProxyUnreachableException
     */
    public static function proxyUnreachable(NetworkException $requestException)
    {
        $message = sprintf(
            'Request to caching proxy at %s failed with message "%s"',
            $requestException->getRequest()->getHeaderLine('Host'),
            $requestException->getMessage()
        );

        return new self(
            $message,
            0,
            $requestException
        );
    }
}
