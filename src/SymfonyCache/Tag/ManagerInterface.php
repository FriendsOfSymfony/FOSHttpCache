<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

interface ManagerInterface
{
    /**
     * Return the concrete cache paths for the given tag.
     *
     * @param string $tag
     * @return string[]
     */
    public function getPathsForTag($tag);

    /**
     * Invlaidate the given tags and return the
     * number of cache entries that have been invalidated.
     *
     * @param string[]
     * @return integer
     */
    public function invalidateTags(array $tags);

    /**
     * Create a new tag for the given content digest.
     *
     * @param string $tag
     * @param string $contentDigest
     * @return void
     */
    public function createTag($tag, $contentDigest);
}
