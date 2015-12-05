<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

/**
 * Implementations assume the responsiblity of invalidating tags.
 */
interface InvalidatorInterface
{
    /**
     * Invlaidate the given tags and return the number of cache entries that
     * have been invalidated.
     *
     * If invalidation requires a HTTP request, an InvalidationRequest may be
     * returned which will subsequently be handled by the Symfony proxy.
     *
     * @param string[]
     * @return integer
     *
     * @return InvalidationRequest|null
     */
    public function invalidateTags(array $tags);

}
