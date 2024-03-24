<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\SymfonyCache;

use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Trait to implement the HttpCacheProvider interface.
 */
trait HttpCacheAware
{
    private ?HttpKernelInterface $httpCache;

    public function getHttpCache(): ?HttpKernelInterface
    {
        return $this->httpCache;
    }

    public function setHttpCache(HttpKernelInterface $httpCache): void
    {
        $this->httpCache = $httpCache;
    }
}
