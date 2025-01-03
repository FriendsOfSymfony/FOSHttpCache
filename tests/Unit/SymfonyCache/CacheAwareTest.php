<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\SymfonyCache;

use FOS\HttpCache\SymfonyCache\HttpCacheAware;
use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
use PHPUnit\Framework\TestCase;
    use Symfony\Component\HttpKernel\HttpKernelInterface;

class CacheAwareTest extends TestCase
{
    private HttpKernelInterface $cacheKernel;

    protected function setUp(): void
    {
        $this->cacheKernel = $this->createMock(HttpKernelInterface::class);
    }

    public function testCacheGetterSetter(): void
    {
        $aware = new AppHttpCacheAware();
        $aware->setHttpCache($this->cacheKernel);
        $this->assertSame($this->cacheKernel, $aware->getHttpCache());
    }
}

class AppHttpCacheAware implements HttpCacheProvider
{
    use HttpCacheAware;
}
