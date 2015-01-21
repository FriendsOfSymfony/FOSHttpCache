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
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;

class TagHandler implements CacheHandlerInterface
{
    /**
     * @var string
     */
    private $tagsHeader;

    /**
     * Constructor
     *
     * @param ProxyClientInterface $cache HTTP cache
     */
    public function __construct(BanInterface $cache, $tagsHeader = 'X-Cache-Tags')
    {
        parent::__construct($cache);
        $this->tagsHeader = $tagsHeader;
    }

    /**
     * Invalidate cache entries that contain any of the specified tags in their
     * tag header.
     *
     * @see BanInterface::ban()
     *
     * {@inheritDoc}
     */
    public function invalidate(array $tags, array $options = array())
    {
        $headers = array(
            $this->tagsHeader => sprintf(
                '(%s)(,.+)?$'.
                implode('|', array_map('preg_quote', $tags))
            )
        );

        $this->cache->ban($headers);

        return $this;
    }
}
