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

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\Exception\UnsupportedProxyOperationException;
use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;

abstract class AbstractCacheHandler implements CacheHandlerInterface
{
    /**
     * @var ProxyClientInterface
     */
    protected $cache;

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
    public function flush()
    {
        return $this->cache->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheClass()
    {
        return get_class($this->cache);
    }
}
