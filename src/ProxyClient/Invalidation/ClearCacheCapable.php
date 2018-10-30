<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient\Invalidation;

use FOS\HttpCache\ProxyClient\ProxyClient;

/**
 * An HTTP cache that supports clearing all of its
 * cache entries.
 *
 * Proxy clients supporting this interface can allow
 * for a more efficient delete-all operation rather
 * than banning everything. Also it serves as
 * an alternative to proxies that do not support
 * banning cache entries at all.
 */
interface ClearCacheCapable extends ProxyClient
{
    /**
     * Clear the cache completely.
     *
     * @return $this
     */
    public function clearCache();
}
