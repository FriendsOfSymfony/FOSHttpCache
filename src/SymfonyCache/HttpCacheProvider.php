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

interface HttpCacheProvider
{
    /**
     * @return HttpKernelInterface|null
     */
    public function getHttpCache();
}
