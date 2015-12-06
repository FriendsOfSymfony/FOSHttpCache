<?php

namespace FOS\HttpCache\Tag;

use Symfony\Component\HttpFoundation\Response;

/**
 * Implementing classes are responsible for associating tags with cache entries
 * and invalidating tags (and removing cache entries).
 */
interface ManagerInterface extends InvalidatorInterface
{
    /**
     * Associate the cache entry identifier (inferred from the HTTP Response
     * as the implementation requires) with the tags (also inferred from the HTTP response).
     *
     * @param Response $response
     * @return void
     */
    public function tagResponse(Response $response);
}
