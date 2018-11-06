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
 * Thrown during setup if the configuration for a proxy client is invalid.
 */
class InvalidUrlException extends InvalidArgumentException
{
    /**
     * @param string $url    the invalid URL
     * @param string $reason Further explanation why the URL was invalid (optional)
     *
     * @return self
     */
    public static function invalidUrl($url, $reason = null)
    {
        $msg = sprintf('URL "%s" is invalid.', $url);
        if ($reason) {
            $msg .= sprintf(' Reason: %s', $reason);
        }

        return new self($msg);
    }

    /**
     * @param string $server  Invalid server
     * @param array  $allowed Allowed URL parts
     *
     * @return self
     */
    public static function invalidUrlParts($server, array $allowed)
    {
        return new self(sprintf(
            'Server "%s" is invalid. Only %s URL parts are allowed.',
            $server,
            implode(', ', $allowed)
        ));
    }
}
