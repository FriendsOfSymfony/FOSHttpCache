<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\SymfonyCache;

use FOS\HttpCache\SymfonyCache\CacheInvalidationInterface;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use FOS\HttpCache\Test\EventDispatchingHttpCacheTestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * This test ensures that the EventDispatchingHttpCache trait is correctly used.
 */
class EventDispatchingHttpCacheTest extends EventDispatchingHttpCacheTestCase
{
    protected function getCacheClass()
    {
        return '\FOS\HttpCache\Tests\Unit\SymfonyCache\AppCache';
    }
}

class AppCache extends HttpCache implements CacheInvalidationInterface
{
    use EventDispatchingHttpCache;

    /**
     * Made public to allow event subscribers to do refresh operations.
     *
     * {@inheritDoc}
     */
    public function fetch(Request $request, $catch = false)
    {
        parent::fetch($request, $catch);
    }
}
