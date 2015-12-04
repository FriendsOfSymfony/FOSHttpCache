<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit;

use FOS\HttpCache\ResponseTagger;

class ResponseTaggerTest extends \PHPUnit_Framework_TestCase
{
    public function testTagResponse()
    {
        $proxyClient = \Mockery::mock('FOS\HttpCache\ProxyClient\Invalidation\TagsInterface')
            ->shouldReceive('getTagsHeaderValue')
            ->with(['post-1', 'test,post'])
            ->once()
            ->andReturn('post-1,test_post')
            ->getMock();

        $tagHandler = new ResponseTagger($proxyClient);
        $this->assertFalse($tagHandler->hasTags());
        $tagHandler->addTags(['post-1', 'test,post']);
        $this->assertTrue($tagHandler->hasTags());
        $this->assertEquals('post-1,test_post', $tagHandler->getTagsHeaderValue());
    }
}
