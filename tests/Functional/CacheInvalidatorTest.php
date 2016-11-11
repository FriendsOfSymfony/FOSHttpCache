<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\Test\VarnishTestCase;

/**
 * @group webserver
 */
class CacheInvalidatorTest extends VarnishTestCase
{
    public function testInvalidateTags()
    {
        $uri = '/tags.php';
        if (getenv('VARNISH_MODULES_VERSION')) {
            $uri .= '?tags_header=xkey';
        }

        $cacheInvalidator = new CacheInvalidator($this->getProxyClient());

        $this->assertMiss($this->getResponse($uri));
        $this->assertHit($this->getResponse($uri));

        $cacheInvalidator->invalidateTags(['tag1']);
        $cacheInvalidator->flush();

        $this->assertMiss($this->getResponse($uri));
    }
}
