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
 * An HTTP cache that supports invalidation by a cache tag, that is, removing, or expiring
 * objects from the cache tagged with a given tag or set of tags.
 */
interface TagsInterface extends ProxyClientInterface
{
    /**
     * Remove/Expire cache objects based on cache tags
     *
     * @param array $tags Tags that should be removed/expired from the cache
     *
     * @return $this
     */
    public function invalidateTags(array $tags);

    /**
     * Get escaped tags
     *
     * @param array $tags
     *
     * @return array
     */
    public function getTagsHeaderValue(array $tags);

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName();
}
