<?php

namespace FOS\HttpCache\Tag;

use Symfony\Component\HttpFoundation\Response;

/**
 * Implementing classes are responsible for associating tags with cache entries
 * and invalidating tags (and removing cache entries).
 */
interface ManagerInterface extends InvalidatorInterface
{
    /**
     * Associate the given cache ID (something which can be associated with a
     * cache entry which can later be invalidated) with the given tags.
     *
     * The $lifetime should be stored with the $cacheId. When invalidating if
     * the $lifetime > 0 and it has expired, then the cache entry should be
     * considered as having already been invalidated by the caching proxy.
     *
     * NOTE: Often this method could simply be a proxy to StorageInterface#tagCacheId.
     *
     * @param string[] $tags
     * @param mixed $cacheId
     * @param int $lifetime
     * @return void
     */
    public function tagCacheId(array $tags, $cacheId, $lifetime);
}
