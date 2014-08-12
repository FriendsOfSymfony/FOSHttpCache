<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient\Invalidation;

use FOS\HttpCache\ProxyClient\ProxyClientInterface;

/**
 * An HTTP cache that supports invalidation by purging, that is, removing one
 * URL from the cache.
 *
 * Implementations should be configurable with a default host to be able to
 * handle purge calls that do not contain a full URL but only a path.
 */
interface PurgeInterface extends ProxyClientInterface
{
    /**
     * Purge a URL
     *
     * Purging a URL will remove the cache for the URL (including the query string)
     *
     * If the $url is just a path, the proxy client class will add a default
     * host name.
     *
     * @param string $url     Path or URL to purge.
     * @param array  $headers Extra HTTP headers to send to the caching proxy
     *                        (optional)
     *
     * @return $this
     */
    public function purge($url, array $headers = array());
}
