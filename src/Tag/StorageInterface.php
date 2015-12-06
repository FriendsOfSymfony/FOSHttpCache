<?php

namespace FOS\HttpCache\Tag;

/**
 * Implementors of this interface associate cache entry identifiers
 * with tags, retrieve cache entry identifiers for tags and remove tags.
 */
interface StorageInterface
{
    /**
     * Associate a list of tags with the given cache identifier.
     * The expriry represents the TIL for the cache entry, it might
     * be the value of the S-MAXAGE header for example.
     *
     * The identifier can be any scalar value which can be associated with a
     * unique HTTP cache entry.
     *
     * @param string[] $tags
     * @param mixed $identifier
     * @param integer $expiry
     * @return void
     */
    public function tagCacheId(array $tags, $identifier, $expiry = null);


    /**
     * Remove the given list of tags from the store.
     *
     * If any of the given tags do not exist, they should be ignored.
     *
     * @param string[]
     * @return void
     */
    public function removeTags(array $tags);

    /**
     * Return the cache identifiers for the given list of tags.
     *
     * @param string[] $tags
     * @return mixed[]
     */
    public function getCacheIds(array $tags);
}
