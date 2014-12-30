<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Handler;

use FOS\HttpCache\ProxyClient\ProxyClientInterface;

class PathHandler implements CacheHandlerInterface
{
    /**
     * Constructor
     *
     * @param ProxyClientInterface $cache HTTP cache
     */
    public function __construct(ProxyClientInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function invalidate($subject, array $options array())
    {
        if (!$this->cache instanceof PurgeInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('PURGE');
        }

        $this->cache->purge($path, $headers);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($path, array $options = array())
    {
        if (!$this->cache instanceof RefreshInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('REFRESH');
        }

        $this->cache->refresh($path, $headers);

        return $this;
    }
}
