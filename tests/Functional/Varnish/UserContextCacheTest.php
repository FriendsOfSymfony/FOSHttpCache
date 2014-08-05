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

/**
 * @group webserver
 * @group varnish
 */
class UserContextCacheTest extends UserContextTestCase
{
    protected function getConfigFile()
    {
        switch ($this->getVarnishVersion()) {
            case '4.0':
                return './tests/Functional/Fixtures/varnish-4/user_context_cache.vcl';
            default:
                return './tests/Functional/Fixtures/varnish-3/user_context_cache.vcl';
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function assertContextCache($status)
    {
        $this->assertEquals('HIT', $status);
    }
}
