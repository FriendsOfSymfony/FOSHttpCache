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

use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;

/**
 * This client implements the interfaces but does nothing.
 *
 * It is useful when testing code that needs a ProxyClient, or to configure in
 * environments that have no caching proxy to talk to, like a local development
 * setup.
 *
 * @author Gavin Staniforth <gavin@gsdev.me>
 */
class Noop implements ProxyClient, BanCapable, PurgeCapable, RefreshCapable, TagCapable, ClearCapable
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

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this;
    }
}
