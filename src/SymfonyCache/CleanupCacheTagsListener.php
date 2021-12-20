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

use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener to remove the cache tags header before the response
 * is delivered to the client so it's not exposed to the world.
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
    public function __construct($tagsHeader = TagHeaderFormatter::DEFAULT_HEADER_NAME)
    {
        $this->tagsHeader = $tagsHeader;
    }

    public function removeTagsHeader(CacheEvent $e)
    {
        if (null === $response = $e->getResponse()) {
            return;
        }

        $response->headers->remove($this->tagsHeader);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::POST_HANDLE => 'removeTagsHeader',
        ];
    }
}
