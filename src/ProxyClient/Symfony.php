<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient;

use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;

/**
 * Symfony HttpCache invalidator.
 *
 * Additional constructor options:
 * - purge_method:         HTTP method that identifies purge requests.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 */
class Symfony extends HttpProxyClient implements PurgeCapable, RefreshCapable, TagCapable, ClearCapable
{
    public const HTTP_METHOD_REFRESH = 'GET';

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $this->queueRequest($this->options['purge_method'], $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        $headers = array_merge($headers, ['Cache-Control' => 'no-cache']);
        $this->queueRequest(self::HTTP_METHOD_REFRESH, $url, $headers);

        return $this;
    }

    protected function configureOptions()
    {
        $resolver = parent::configureOptions();
        $resolver->setDefaults([
            'purge_method' => PurgeListener::DEFAULT_PURGE_METHOD,
            'clear_cache_header' => PurgeListener::DEFAULT_CLEAR_CACHE_HEADER,
            'tags_method' => PurgeTagsListener::DEFAULT_TAGS_METHOD,
            'tags_header' => PurgeTagsListener::DEFAULT_TAGS_HEADER,
            'tags_invalidate_path' => '/',
            'header_length' => 7500,
        ]);
        $resolver->setAllowedTypes('purge_method', 'string');
        $resolver->setAllowedTypes('clear_cache_header', 'string');
        $resolver->setAllowedTypes('tags_method', 'string');
        $resolver->setAllowedTypes('tags_header', 'string');
        $resolver->setAllowedTypes('tags_invalidate_path', 'string');
        $resolver->setAllowedTypes('header_length', 'int');

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $escapedTags = $this->escapeTags($tags);

        $chunkSize = $this->determineTagsPerHeader($escapedTags, ',');

        foreach (array_chunk($escapedTags, $chunkSize) as $tagchunk) {
            $this->queueRequest(
                $this->options['tags_method'],
                $this->options['tags_invalidate_path'],
                [$this->options['tags_header'] => implode(',', $tagchunk)],
                false
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Clearing the cache is implemented with a purge request with a special
     * header to indicate that the whole cache should be removed.
     *
     * @return $this
     */
    public function clear()
    {
        $this->queueRequest(
            $this->options['purge_method'],
            '/',
            [$this->options['clear_cache_header'] => 'true'],
            false
        );

        return $this;
    }
}
