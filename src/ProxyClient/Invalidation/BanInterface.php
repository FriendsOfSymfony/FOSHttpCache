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
 * An HTTP cache that supports invalidation by banning, that is, removing
 * objects from the cache that match a regular expression
 */
interface BanInterface extends ProxyClientInterface
{
    const REGEX_MATCH_ALL = '.*';
    const CONTENT_TYPE_ALL = self::REGEX_MATCH_ALL;

    /**
     * Ban cached objects matching HTTP headers
     *
     * Each header is either a:
     * - regular string ('X-Host' => 'example.com')
     * - or a POSIX regular expression ('X-Host' => '^(www\.)?(this|that)\.com$').
     *
     * Please make sure to configure your HTTP caching proxy to set the headers
     * supplied here on the cached objects. So if you want to match objects by
     * host name, configure your proxy to copy the host to a custom HTTP header
     * such as X-Host.
     *
     * @param array $headers HTTP headers that path must match to be banned.
     *
     * @return $this
     */
    public function ban(array $headers);

    /**
     * Ban URLs based on a regular expression for the URI, an optional
     * content type and optional limit to certain hosts.
     *
     * The hosts parameter can either be a regular expression, e.g.
     * '^(www\.)?(this|that)\.com$' or an array of exact host names, e.g.
     * array('example.com', 'other.net'). If the parameter is empty, all hosts
     * are matched.

     * @param string       $path        Regular expression pattern for URI to
     *                                  invalidate.
     * @param string       $contentType Regular expression pattern for the content
     *                                  type to limit banning, for instance 'text'.
     * @param array|string $hosts       Regular expression of a host name or list
     *                                  of exact host names to limit banning.
     *
     * @return $this
     */
    public function banPath($path, $contentType = null, $hosts = null);
}
