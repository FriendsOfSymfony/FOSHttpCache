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
    /**
     * @var HttpKernelInterface|null
     */
    private $httpCache;

    /**
     * @return HttpKernelInterface|null
     */
    public function getHttpCache()
    {
        return $this->httpCache;
    }

    public function setHttpCache(HttpKernelInterface $httpCache)
    {
        $this->httpCache = $httpCache;
    }
}
