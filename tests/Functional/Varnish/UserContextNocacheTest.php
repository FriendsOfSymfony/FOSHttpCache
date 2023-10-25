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
class UserContextNocacheTest extends UserContextTestCase
{
    protected function getConfigFile()
    {
        switch ((int) $this->getVarnishVersion()) {
            case 3:
                return dirname(__DIR__).'/Fixtures/varnish-3/user_context_nocache.vcl';
            default:
                return dirname(__DIR__).'/Fixtures/varnish/user_context_nocache.vcl';
        }
    }

    protected function assertContextCache($status)
    {
        $this->assertEquals('MISS', $status);
    }
}
