<?php

namespace FOS\HttpCache\Tag;

/**
 * Implementing classes are responsible for associating tags with cache entries
 * and invalidating tags (and removing cache entries).
 */
interface ManagerInterface
{
    /**
     * Associate the cache entry identifier (inferred from the HTTP Response
     * as the implementation requires) with the given tags.
     *
     * @param string[] $tags
     * @param Response $response
     * @return void
     */
    public function tagResponse(array $tags, Response $response);

    /**
     * Invalidate the cache entries associated with any of the given list of tags.
     *
     * @param string[] $tags
     */
    public function invalidateTags(array $tags);
}
