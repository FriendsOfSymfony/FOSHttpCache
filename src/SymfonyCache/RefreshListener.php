<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\SymfonyCache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Refresh handler for the symfony built-in HttpCache.
 *
 * To use this handler, make sure that your HttpCache makes the fetch method
 * public.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class RefreshListener extends AccessControlledListener
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => 'handleRefresh',
        ];
    }

    /**
     * Look at cacheable requests and handle refresh requests.
     *
     * When the request comes from a non-authorized client, ignore refresh to
     * let normal lookup happen.
     */
    public function handleRefresh(CacheEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->isMethodCacheable()
            || !$request->isNoCache()
            || !$this->isRequestAllowed($request)
        ) {
            return;
        }

        $event->setResponse(
            $event->getKernel()->fetch($request)
        );
    }
}
