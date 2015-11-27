<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

class NullManager implements ManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPathsForTag($tag)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createTag($tag, $contentDigest)
    {
    }
}
