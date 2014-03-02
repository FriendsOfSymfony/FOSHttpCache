<?php

namespace FOS\HttpCache\Exception;

/**
 * Thrown during setup if the configuration for a cache proxy is invalid.
 */
class InvalidUrlException extends InvalidArgumentException implements HttpCacheExceptionInterface
{
    /**
     * @param string $url    The invalid URL.
     * @param string $reason Further explanation why the URL was invalid (optional)
     *
     * @return InvalidUrlException
     */
    public static function invalidUrl($url, $reason = null)
    {
        $msg = sprintf('URL "%s" is invalid.', $url);
        if ($reason) {
            $msg .= sprintf('Reason: %s', $reason);
        }

        return new InvalidUrlException($msg);
    }

    /**
     * @param string $server  Invalid server
     * @param array  $allowed Allowed URL parts
     *
     * @return InvalidUrlException
     */
    public static function invalidUrlParts($server, array $allowed)
    {
        return new InvalidUrlException(sprintf(
            'Server "%s" is invalid. Only %s URL parts are allowed.',
            $server,
            implode(', ', $allowed)
        ));
    }

    /**
     * @param string $url     Requested full URL
     * @param string $scheme  Requested URL scheme
     * @param array  $allowed Supported URL schemes
     *
     * @return InvalidUrlException
     */
    public static function invalidUrlScheme($url, $scheme, array $allowed)
    {
        return new InvalidUrlException(sprintf(
            'Host "%s" with scheme "%s" is invalid. Only schemes "%s" are supported',
            $url,
            $scheme,
            implode(', ', $allowed)
        ));
    }
}
