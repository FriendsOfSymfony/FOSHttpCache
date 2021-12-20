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
 *
 * {@inheritdoc}
 */
class RefreshListener extends AccessControlledListener
{
    /**
     * {@inheritdoc}
     */
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
    public function handleRefresh(CacheEvent $event)
    {
        $request = $event->getRequest();
        // BC - we can drop this check when we only support Symfony 3.1 and newer
        $cacheable = method_exists(Request::class, 'isMethodCacheable')
            ? $request->isMethodCacheable()
            : $request->isMethodSafe(false);

        if (!$cacheable
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
