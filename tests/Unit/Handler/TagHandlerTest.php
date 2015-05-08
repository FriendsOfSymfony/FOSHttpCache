<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Handler;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\Handler\TagHandler;

class TagHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidateTags()
    {
        $cacheInvalidator = \Mockery::mock('FOS\HttpCache\CacheInvalidator')
            ->shouldReceive('invalidate')
            ->with(array('X-Cache-Tags' => '(post\-1|posts)(,.+)?$'))
            ->once()
            ->shouldReceive('supports')
            ->with(CacheInvalidator::INVALIDATE)
            ->once()
            ->andReturn(true)
            ->getMock();

        $tagHandler = new TagHandler($cacheInvalidator);
        $tagHandler->invalidateTags(array('post-1', 'posts'));
    }

    public function testInvalidateTagsCustomHeader()
    {
        $cacheInvalidator = \Mockery::mock('FOS\HttpCache\CacheInvalidator')
            ->shouldReceive('invalidate')
            ->with(array('Custom-Tags' => '(post\-1)(,.+)?$'))
            ->once()
            ->shouldReceive('supports')
            ->with(CacheInvalidator::INVALIDATE)
            ->once()
            ->andReturn(true)
            ->getMock();

        $tagHandler = new TagHandler($cacheInvalidator, 'Custom-Tags');
        $this->assertEquals('Custom-Tags', $tagHandler->getTagsHeaderName());
        $tagHandler->invalidateTags(array('post-1'));
    }

    public function testEscapingTags()
    {
        $cacheInvalidator = \Mockery::mock('FOS\HttpCache\CacheInvalidator')
            ->shouldReceive('invalidate')
            ->with(array('X-Cache-Tags' => '(post_test)(,.+)?$'))
            ->once()
            ->shouldReceive('supports')
            ->with(CacheInvalidator::INVALIDATE)
            ->once()
            ->andReturn(true)
            ->getMock();

        $tagHandler = new TagHandler($cacheInvalidator);
        $tagHandler->invalidateTags(array('post,test'));
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\UnsupportedProxyOperationException
     */
    public function testInvalidateUnsupported()
    {
        $cacheInvalidator = \Mockery::mock('FOS\HttpCache\CacheInvalidator')
            ->shouldReceive('supports')
            ->with(CacheInvalidator::INVALIDATE)
            ->once()
            ->andReturn(false)
            ->getMock();

        new TagHandler($cacheInvalidator);
    }

    public function testTagResponse()
    {
        $cacheInvalidator = \Mockery::mock('FOS\HttpCache\CacheInvalidator')
            ->shouldReceive('supports')
            ->with(CacheInvalidator::INVALIDATE)
            ->once()
            ->andReturn(true)
            ->getMock();

        $tagHandler = new TagHandler($cacheInvalidator);
        $this->assertFalse($tagHandler->hasTags());
        $tagHandler->addTags(array('post-1', 'test,post'));
        $this->assertTrue($tagHandler->hasTags());
        $this->assertEquals('post-1,test_post', $tagHandler->getTagsHeaderValue());
    }
}
