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
class Symfony extends HttpProxyClient implements PurgeCapable, RefreshCapable, TagCapable
{
    const HTTP_METHOD_REFRESH = 'GET';

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
        $resolver->setDefault('purge_method', PurgeListener::DEFAULT_PURGE_METHOD);
        $resolver->setAllowedTypes('purge_method', 'string');
        $resolver->setDefault('purge_tags_method', PurgeTagsListener::DEFAULT_PURGE_TAGS_METHOD);
        $resolver->setAllowedTypes('purge_tags_method', 'string');
        $resolver->setDefault('purge_tags_header', PurgeTagsListener::DEFAULT_PURGE_TAGS_HEADER);
        $resolver->setAllowedTypes('purge_tags_header', 'string');
        $resolver->setDefault('purge_tags_header_length', 7500);
        $resolver->setAllowedTypes('purge_tags_header_length', 'int');

        return $resolver;
    }

    /**
     * Remove/Expire cache objects based on cache tags.
     *
     * @param array $tags Tags that should be removed/expired from the cache
     *
     * @return $this
     */
    public function invalidateTags(array $tags)
    {
        $escapedTags = $this->escapeTags($tags);

        $chunks = $this->splitLongHeaderValue(implode(',', $escapedTags), $this->options['purge_tags_header_length']);

        foreach ($chunks as $chunk) {
            $this->queueRequest(
                $this->options['purge_tags_method'],
                '/',
                [$this->options['purge_tags_header'] => $chunk]
            );
        }
    }
}
