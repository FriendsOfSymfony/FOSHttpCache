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

class HeadersHandler implements CacheHandlerInterface
{
    /**
     * Constructor
     *
     * @param ProxyClientInterface $cache HTTP cache
     */
    public function __construct(BanInterface $cache)
    {
        parent::__construct($cache);
    }

    /**
     * Invalidate all cached objects matching the provided HTTP headers.
     *
     * Each header is a a POSIX regular expression, for example
     * array('X-Host' => '^(www\.)?(this|that)\.com$')
     *
     * @see BanInterface::ban()
     *
     * Options: No options
     *
     * {@inheritDoc}
     */
    public function invalidate($headers, array $options = array())
    {
        if (!is_array($headers)) {
            throw new InvalidArgumentException(sprintf(
                'You must pass an array of headers to %s::invalidate',
                get_class($this)
            ));
        }

        $this->cache->ban($headers);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh()
    {
    }
}
