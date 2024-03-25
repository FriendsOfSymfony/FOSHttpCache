<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\Varnish;

use PHPUnit\Framework\Attributes\Group;

#[Group('webserver')]
#[Group('varnish')]
class UserContextCacheTest extends UserContextTestCase
{
    protected function getConfigFile(): string
    {
        return match ((int) $this->getVarnishVersion()) {
            3 => dirname(__DIR__).'/Fixtures/varnish-3/user_context_cache.vcl',
            default => dirname(__DIR__).'/Fixtures/varnish/user_context_cache.vcl',
        };
    }

    protected function assertContextCache(string $hashCache): void
    {
        $this->assertEquals('HIT', $hashCache);
    }
}
