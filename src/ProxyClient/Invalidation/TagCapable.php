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
 * An HTTP cache that supports invalidation by a cache tag, that is, removing
 * or expiring objects from the cache tagged with a given tag or set of tags.
 *
 * HTTP responses must carry the tags header name with the tags header value
 * for tag invalidation to work.
 */
interface TagCapable extends ProxyClient
{
    /**
     * Remove/Expire cache objects based on cache tags.
     *
     * @param array $tags Tags that should be removed/expired from the cache
     *
     * @return $this
     */
    public function invalidateTags(array $tags);

    /**
     * Get value for the tags header as it should be included in the response.
     *
     * This does all necessary escaping and concatenates the tags in a way that
     * individual tags can be invalidated by this client.
     *
     * @param array $tags
     *
     * @return string
     */
    public function getTagsHeaderValue(array $tags);

    /**
     * Get the HTTP header name for the cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName();
}
