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
 * Listener that allows to cleanup the cache tags header so it's not exposed
 * to the world.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class CleanupCacheTagsListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $tagsHeader;

    /**
     * @param string $tagsHeader The header that is used for cache tags
     */
    public function __construct($tagsHeader = PurgeTagsListener::DEFAULT_TAGS_HEADER)
    {
        $this->tagsHeader = $tagsHeader;
    }

    /**
     * Remove the cache headers.
     *
     * @param CacheEvent $e
     */
    public function cleanResponse(CacheEvent $e)
    {
        if (null === ($response = $e->getResponse())) {
            return;
        }

        $response->headers->remove($this->tagsHeader);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::POST_HANDLE => 'cleanResponse',
        ];
    }
}
