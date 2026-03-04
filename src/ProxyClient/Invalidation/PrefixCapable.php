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
 * An HTTP cache that supports invalidation by a prefix, that is, removing
 * or expiring objects from the cache starting with the given string or strings.
 */
interface PrefixCapable extends ProxyClient
{
    /**
     * Remove/Expire cache objects based on URL prefixes.
     *
     * @param string[] $prefixes Prefixed objects that should be removed/expired from the cache. An empty prefix list should be ignored.
     */
    public function invalidatePrefixes(array $prefixes): static;
}
