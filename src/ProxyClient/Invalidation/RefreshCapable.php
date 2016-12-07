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
 * An HTTP cache that supports invalidation by refresh requests that force a
 * cache miss for one specific URL.
 */
interface RefreshCapable extends ProxyClient
{
    /**
     * Refresh a URL.
     *
     * Refreshing a URL will generate a new cached response for this request,
     * including the query string.
     *
     * If the HTTP client uses the AddHostPlugin, $url can also be only a path.
     *
     * Example:
     *
     *    $client
     *        ->refresh('http://my-app.com/some/path')
     *        ->refresh('other/path')
     *        ->flush()
     *    ;
     *
     * You can specify HTTP headers for the request. Those headers will be
     * forwarded by the proxy cache to your application.
     *
     * If you use a Vary header, you need to refresh each variant separately,
     * as the proxy server does not know which variants exist. To refresh the
     * JSON representation of an URL:
     *
     *    $client
     *        ->refresh('/some/path', ['Accept' => 'application/json'])
     *        ->flush()
     *    ;
     *
     * @param string $url     Path or URL to refresh
     * @param array  $headers Extra HTTP headers to send to the caching proxy
     *                        (optional)
     *
     * @return $this
     */
    public function refresh($url, array $headers = []);
}
