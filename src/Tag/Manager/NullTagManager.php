<?php

namespace FOS\HttpCache\Tag\Manager;

use FOS\HttpCache\Tag\ManagerInterface;

/**
 * Null tag manager - use this manager when no tagging is required.
 */
class NullTagManager implements ManagerInterface
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
