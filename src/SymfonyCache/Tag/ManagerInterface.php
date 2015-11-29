<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

/**
 * Symfony HTTP cache tag manager. 
 *
 * Classes implementing this interface are responsible for associating cache
 * entries with tags and invalidating cache entries associated with a given
 * tag.
 */
interface ManagerInterface
{
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
