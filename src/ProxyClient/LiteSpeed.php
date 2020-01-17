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

/**
 * LiteSpeed (OpenLiteSpeed or LiteSpeed Web Server) invalidator.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class LiteSpeed extends HttpProxyClient implements PurgeCapable, TagCapable, ClearCapable, RefreshCapable
{
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->queueRequest('PURGE', $this->options['purge_endpoint'], [
            'X-LiteSpeed-Purge' => '*',
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        // LiteSpeed only supports purging by relative URLs
        $urlParts = parse_url($url);
        $url = array_key_exists('path', $urlParts) ? $urlParts['path'] : '/';

        $this->queueRequest('PURGE', $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();
        $resolver->setDefaults(['purge_endpoint' => '/_fos_litespeed_purge_endpoint']);
        $resolver->setAllowedTypes('purge_endpoint', 'string');

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $this->queueRequest('PURGE', $this->options['purge_endpoint'], [
            'X-LiteSpeed-Purge' => implode(', ', preg_filter('/^/', 'tag=', $tags)),
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        $this->purge($url);
        $this->queueRequest('GET', $url, $headers);

        return $this;
    }
}
