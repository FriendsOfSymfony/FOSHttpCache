<?php

namespace FOS\HttpCache\Tag\Manager;

/**
 * Null tag manager - use this manager when no tagging is required.
 */
class NullTagManager
{
    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function tagCacheId(array $tags, $cacheId, $lifetime)
    {
    }
}
