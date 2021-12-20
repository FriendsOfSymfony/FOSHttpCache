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
 * Custom TTL handler for the symfony built-in HttpCache.
 *
 * This allows to use a custom header to control time to live in HttpCache and
 * keep s-maxage for 3rd party proxies.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
class CustomTtlListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $ttlHeader;

    /**
     * @var bool
     */
    private $keepTtlHeader;

    /**
     * Header used for backing up the s-maxage.
     *
     * @var string
     */
    public const SMAXAGE_BACKUP = 'FOS-Smaxage-Backup';

    /**
     * @param string $ttlHeader     The header name that is used to specify the time to live
     * @param bool   $keepTtlHeader Keep the custom TTL header on the response for later usage (e.g. debugging)
     */
    public function __construct($ttlHeader = 'X-Reverse-Proxy-TTL', $keepTtlHeader = false)
    {
        $this->ttlHeader = $ttlHeader;
        $this->keepTtlHeader = $keepTtlHeader;
    }

    /**
     * Use the TTL from the custom header rather than the default one.
     *
     * If there is such a header, the original s_maxage is backed up to the
     * static::SMAXAGE_BACKUP header.
     */
    public function useCustomTtl(CacheEvent $e)
    {
        $response = $e->getResponse();
        if (!$response->headers->has($this->ttlHeader)) {
            return;
        }
        $backup = $response->headers->hasCacheControlDirective('s-maxage')
            ? $response->headers->getCacheControlDirective('s-maxage')
            : 'false'
        ;
        $response->headers->set(static::SMAXAGE_BACKUP, $backup);
        $response->setTtl($response->headers->get($this->ttlHeader));
    }

    /**
     * Remove the custom TTL header and restore s_maxage from the backup.
     */
    public function cleanResponse(CacheEvent $e)
    {
        $response = $e->getResponse();
        if (!$response->headers->has($this->ttlHeader)
            && !$response->headers->has(static::SMAXAGE_BACKUP)
        ) {
            return;
        }

        if ($response->headers->has(static::SMAXAGE_BACKUP)) {
            $smaxage = $response->headers->get(static::SMAXAGE_BACKUP);
            if ('false' === $smaxage) {
                $response->headers->removeCacheControlDirective('s-maxage');
            } else {
                $response->headers->addCacheControlDirective('s-maxage', $smaxage);
            }
        }

        if (!$this->keepTtlHeader) {
            $response->headers->remove($this->ttlHeader);
        }
        $response->headers->remove(static::SMAXAGE_BACKUP);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_STORE => 'useCustomTtl',
            Events::POST_HANDLE => 'cleanResponse',
        ];
    }
}
