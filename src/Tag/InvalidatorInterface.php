<?php

namespace FOS\HttpCache\Tag;

interface InvalidatorInterface
{
    /**
     * Invalidate the cache entries associated with any of the given list of tags.
     *
     * The Invalidator may return an InvalidationRequest.
     *
     * @param string[] $tags
     */
    public function invalidateTags(array $tags);
}
