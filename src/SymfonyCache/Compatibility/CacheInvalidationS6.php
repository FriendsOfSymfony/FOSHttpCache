<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\SymfonyCache\Compatibility;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Interface for a HttpCache that supports active cache invalidation.
 *
 * Method signature of `fetch` and `getStore` compatible with Symfony 6 or newer.
 */
interface CacheInvalidationS6 extends HttpKernelInterface
{
    /**
     * Forwards the Request to the backend and determines whether the response should be stored.
     *
     * This methods is triggered when the cache missed or a reload is required.
     *
     * This method is present on HttpCache but must be public to allow event listeners to do
     * refresh operations.
     *
     * @param Request $request A Request instance
     * @param bool    $catch   Whether to process exceptions
     *
     * @return Response A Response instance
     */
    public function fetch(Request $request, bool $catch = false): Response;

    /**
     * Gets the store for cached responses.
     *
     * @return StoreInterface $store The store used by the HttpCache
     */
    public function getStore(): StoreInterface;
}
