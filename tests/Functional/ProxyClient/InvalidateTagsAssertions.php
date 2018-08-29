<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\ProxyClient;

use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;

/**
 * Assertions that do the cache tag invalidation operations.
 */
trait InvalidateTagsAssertions
{
    /**
     * Asserting that purging cache tags leads to invalidated content.
     *
     * @param PurgeCapable $proxyClient The client to send purge instructions to the cache
     * @param array        $tags        The cache tags to invalidate
     * @param string       $path        The path to get and purge, defaults to /tags.php
     */
    protected function assertInvalidateTags(TagCapable $proxyClient, array $cacheTags, $path = '/tags.php')
    {
        $this->assertMiss($this->getResponse($path));
        $this->assertHit($this->getResponse($path));

        $proxyClient->invalidateTags($cacheTags)->flush();
        $this->assertMiss($this->getResponse($path));
    }
}
