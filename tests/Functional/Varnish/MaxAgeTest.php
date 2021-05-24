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
class MaxAgeTest extends VarnishTestCase
{
    protected function getConfigFile()
    {
        switch ((int) $this->getVarnishVersion()) {
            case 3:
                return './tests/Functional/Fixtures/varnish-3/custom_ttl.vcl';
            default:
                return './tests/Functional/Fixtures/varnish/custom_ttl.vcl';
        }
    }

    public function testCustomTtl()
    {
        $this->assertMiss($this->getResponse('/cache-experiment.php'));
        sleep(4);
        $response = $this->getResponse('/cache-experiment.php');
        $this->assertHit($response);

        var_dump($response->getHeaders());
    }
}
