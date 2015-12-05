<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

/**
 * Null tag manager - use this manager in a dev environment.
 */
class NullManager implements ManagerInterface
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
    public function tagDigest($tag, $contentDigest)
    {
        die('tagDigeest');
    }
}
