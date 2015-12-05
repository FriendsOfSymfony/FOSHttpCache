<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

/**
 * Symfony HTTP cache tag manager. 
 *
 * Implementations are responsible for associating cache entries with tags and
 * invalidating cache entries associated with a given tag.
 */
interface ManagerInterface extends InvalidatorInterface
{
    /**
     * Create a new tag for the given content digest.
     *
     * @param string $tag
     * @param string $contentDigest
     * @return void
     */
    public function tagDigest($tag, $contentDigest);
}
