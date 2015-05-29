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
use FOS\HttpCache\Handler\TagHandler;
use FOS\HttpCache\Test\VarnishTestCase;

/**
 * @group webserver
 */
class CacheInvalidatorTest extends VarnishTestCase
{
    public function testInvalidateTags()
    {
        $cacheInvalidator = new CacheInvalidator($this->getProxyClient());
        $tagHandler = new TagHandler($cacheInvalidator);

        $this->assertMiss($this->getResponse('/tags.php'));
        $this->assertHit($this->getResponse('/tags.php'));

        $tagHandler->invalidateTags(array('tag1'));
        $cacheInvalidator->flush();

        $this->assertMiss($this->getResponse('/tags.php'));
    }

    /**
     * Test the deprecated CacheInvalidator::invalidateTags method.
     */
    public function testInvalidateTagsBC()
    {
        $cacheInvalidator = new CacheInvalidator($this->getProxyClient());

        $this->assertMiss($this->getResponse('/tags.php'));
        $this->assertHit($this->getResponse('/tags.php'));

        $cacheInvalidator->invalidateTags(array('tag1'))->flush();

        $this->assertMiss($this->getResponse('/tags.php'));
    }
}
