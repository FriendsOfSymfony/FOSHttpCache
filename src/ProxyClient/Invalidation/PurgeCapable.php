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

use FOS\HttpCache\ProxyClient\ProxyClient;

/**
 * An HTTP cache that supports invalidation by purging: Remove one URL from the cache.
 */
interface PurgeCapable extends ProxyClient
{
    /**
     * Purge a URL.
     *
     * Purging a URL will remove the cache for the URL (including the query string)
     *
     * If the HTTP client uses the AddHostPlugin, $url can also be only a path.
     *
     * Example:
     *
     *    $client
     *        ->purge('http://my-app.com/some/path')
     *        ->purge('/other/path')
     *        ->flush()
     *    ;
     *
     * Please note that purge will invalidate all variants, so you do not need
     * to specify variants headers, such as ``Accept``.
     *
     * @param string $url     Path or URL to purge
     * @param array  $headers Extra HTTP headers to send to the caching proxy
     *                        (optional)
     *
     * @return $this
     */
    public function purge($url, array $headers = []);
}
