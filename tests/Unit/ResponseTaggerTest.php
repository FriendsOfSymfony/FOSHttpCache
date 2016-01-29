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
use FOS\HttpCache\ProxyClient\Varnish;
use FOS\HttpCache\Exception\InvalidTagException;

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

    public function testStrictEmptyTag()
    {
        $proxyClient = new Varnish(['localhost']);

        $tagHandler = new ResponseTagger($proxyClient, array('strict' => true));

        try {
            $tagHandler->addTags(array('post-1', false));
            $this->fail('Expected exception');
        } catch (InvalidTagException $e) {
            // success
        }
    }

    public function testNonStrictEmptyTag()
    {
        $proxyClient = \Mockery::mock('FOS\HttpCache\ProxyClient\Invalidation\TagsInterface')
            ->shouldReceive('getTagsHeaderValue')
            ->with(['post-1'])
            ->once()
            ->andReturn('post-1')
            ->getMock();

        $tagHandler = new ResponseTagger($proxyClient);
        $tagHandler->addTags(array('post-1', false, null, ''));
        $this->assertTrue($tagHandler->hasTags());
        $this->assertEquals('post-1', $tagHandler->getTagsHeaderValue());
    }
}
