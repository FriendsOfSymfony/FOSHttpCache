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
class MissingHostException extends \RuntimeException implements HttpCacheException
{
    /**
     * @param string $path the path that was asked to be invalidated
     *
     * @return MissingHostException
     */
    public static function missingHost($path)
    {
        $msg = sprintf(
            'Path "%s" cannot be invalidated without a host. '
            .'Either invalidate with absolute URLs including the host name '
            .'or configure the base URI on the HttpDispatcher.',
            $path
        );

        return new self($msg);
    }
}
