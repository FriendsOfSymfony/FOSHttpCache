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
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_HANDLE => 'handleRefresh',
        ];
    }

    /**
     * Look at safe requests and handle refresh requests.
     *
     * Ignore refresh to let normal lookup happen when the request comes from
     * a non-authorized client.
     *
     * @param CacheEvent $event
     */
    public function handleRefresh(CacheEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->isMethodSafe()
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
