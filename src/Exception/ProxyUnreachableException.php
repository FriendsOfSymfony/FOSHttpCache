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

use Http\Client\Exception\RequestException;

/**
 * Thrown when a request to the reverse caching proxy fails to establish a
 * connection.
 */
class ProxyUnreachableException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param RequestException $adapterException
     *
     * @return ProxyUnreachableException
     */
    public static function proxyUnreachable(RequestException $adapterException)
    {
        $message = sprintf(
            'Request to caching proxy at %s failed with message "%s"',
            $adapterException->getRequest()->getHeaderLine('Host'),
            $adapterException->getMessage()
        );

        return new ProxyUnreachableException(
            $message,
            0,
            $adapterException
        );
    }
}
