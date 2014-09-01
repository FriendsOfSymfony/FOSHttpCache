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
 * Thrown when there is no default host configured and an invalidation request
 * with just a path is made.
 */
class MissingHostException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param string $path The path that was asked to be invalidated.
     *
     * @return MissingHostException
     */
    public static function missingHost($path)
    {
        $msg = sprintf(
            'Path "%s" cannot be invalidated without a host. '
            . 'Either invalidate full URLs containing hostnames instead of paths '
            . 'or configure the caching proxy class with a hostname in the base path.',
            $path
        );

        return new MissingHostException($msg);
    }
}
