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
 * An HTTP cache that supports removing all of its cache entries.
 *
 * This operation allows to clear proxies that do not support banning.
 * Additionally, this operation is likely more efficient than a ban request
 * that matches everything.
 */
interface ClearCapable extends ProxyClient
{
    /**
     * Remove all cache items from this cache.
     *
     * @return $this
     */
    public function clear();
}
