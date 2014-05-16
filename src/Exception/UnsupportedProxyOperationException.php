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
 * Thrown when the CacheInvalidator is asked for an operation the underlying
 * cache proxy does not support.
 */
class UnsupportedProxyOperationException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param string $method Name of the HTTP method that would be required.
     *
     * @return UnsupportedProxyOperationException
     */
    public static function cacheDoesNotImplement($method)
    {
        return new self(sprintf('HTTP cache does not support %s requests', $method));
    }
}
