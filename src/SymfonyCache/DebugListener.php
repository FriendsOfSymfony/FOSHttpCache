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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Debug handler for the symfony built-in HttpCache.
 *
 * Add debug information to the response for use in cache tests.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
class DebugListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::POST_HANDLE => 'handleDebug',
        ];
    }

    /**
     * Extract the cache HIT/MISS information from the X-Symfony-Cache header.
     *
     * For this header to be present, the HttpCache must be created with the
     * debug option set to true.
     */
    public function handleDebug(CacheEvent $event)
    {
        $response = $event->getResponse();
        if ($response->headers->has('X-Symfony-Cache')) {
            if (false !== strpos($response->headers->get('X-Symfony-Cache'), 'miss')) {
                $state = 'MISS';
            } elseif (false !== strpos($response->headers->get('X-Symfony-Cache'), 'fresh')) {
                $state = 'HIT';
            } else {
                $state = 'UNDETERMINED';
            }
            $response->headers->set('X-Cache', $state);
        }
    }
}
