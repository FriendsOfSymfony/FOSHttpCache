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

use FOS\HttpCache\Test\VarnishTestCase;

/**
 * @group webserver
 * @group varnish
 */
class CustomTtlTest extends VarnishTestCase
{
    protected function getConfigFile()
    {
        switch ((int) $this->getVarnishVersion()) {
            case 3:
                return dirname(__DIR__).'/Fixtures/varnish-3/custom_ttl.vcl';
            default:
                return dirname(__DIR__).'/Fixtures/varnish/custom_ttl.vcl';
        }
    }

    public function testCustomTtl()
    {
        $this->assertMiss($this->getResponse('/custom-ttl.php'));
        $this->assertHit($this->getResponse('/custom-ttl.php'));
    }
}
