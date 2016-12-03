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

use FOS\HttpCache\ProxyClient\Invalidation\Ban;
use FOS\HttpCache\ProxyClient\Invalidation\Purge;
use FOS\HttpCache\ProxyClient\Invalidation\Refresh;
use FOS\HttpCache\ProxyClient\Invalidation\Tags;

/**
 * This is a no operation client, its only purpose is to provide an implementation for use in an enviroments that
 * have no proxy to use.
 *
 * @author Gavin Staniforth <gavin@gsdev.me>
 */
class Noop implements ProxyClient, Ban, Purge, Refresh, Tags
{
    /**
     * {@inheritdoc}
     */
    public function ban(array $headers)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function banPath($path, $contentType = null, $hosts = null)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderValue(array $tags)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderName()
    {
        return 'X-Noop-Cache-Tags';
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return 0;
    }
}
