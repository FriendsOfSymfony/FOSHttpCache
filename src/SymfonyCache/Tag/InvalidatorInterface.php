<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

interface InvalidatorInterface
{
    /**
     * Invlaidate the given tags and return the
     * number of cache entries that have been invalidated.
     *
     * @param string[]
     * @return integer
     */
    public function invalidateTags(array $tags);

}
