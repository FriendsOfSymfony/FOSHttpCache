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

use FOS\HttpCache\Exception\InvalidTagException;
use FOS\HttpCache\ResponseTagger;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ResponseTaggerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDefaultFormatter()
    {
        $tagger = new ResponseTagger();
        $this->assertEquals('X-Cache-Tags', $tagger->getTagsHeaderName());
    }

    public function testGetTagsHeaderValue()
    {
        $headerFormatter = \Mockery::mock(TagHeaderFormatter::class)
            ->shouldReceive('getTagsHeaderValue')
            ->with(['post-1', 'test,post'])
            ->once()
            ->andReturn('post-1,test_post')
            ->getMock();

        $tagger = new ResponseTagger(['header_formatter' => $headerFormatter]);
        $this->assertFalse($tagger->hasTags());
        $tagger->addTags(['post-1', 'test,post']);
        $this->assertTrue($tagger->hasTags());
        $this->assertEquals('post-1,test_post', $tagger->getTagsHeaderValue());
        $this->assertTrue($tagger->hasTags()); // getting the header does not clear the tags
    }

    public function testTagResponseReplace()
    {
        $headerFormatter = \Mockery::mock(TagHeaderFormatter::class)
            ->shouldReceive('getTagsHeaderValue')
            ->with(['tag-1', 'tag-2'])
            ->once()
            ->andReturn('tag-1,tag-2')
            ->shouldReceive('getTagsHeaderName')
            ->once()
            ->andReturn('FOS-Tags')
            ->getMock();

        $tagger = new ResponseTagger(['header_formatter' => $headerFormatter]);

        $response = \Mockery::mock(ResponseInterface::class)
            ->shouldReceive('withHeader')
            ->with('FOS-Tags', 'tag-1,tag-2')
            ->getMock();

        $tagger->addTags(['tag-1', 'tag-2']);
        $tagger->tagResponse($response, true);
        $this->assertFalse($tagger->hasTags());
    }

    public function testTagResponseAdd()
    {
        $headerFormatter = \Mockery::mock(TagHeaderFormatter::class)
            ->shouldReceive('getTagsHeaderValue')
            ->with(['tag-1', 'tag-2'])
            ->once()
            ->andReturn('tag-1,tag-2')
            ->shouldReceive('getTagsHeaderName')
            ->once()
            ->andReturn('FOS-Tags')
            ->getMock();

        $tagger = new ResponseTagger(['header_formatter' => $headerFormatter]);

        $response = \Mockery::mock(ResponseInterface::class)
            ->shouldReceive('withAddedHeader')
            ->with('FOS-Tags', 'tag-1,tag-2')
            ->getMock();

        $tagger->addTags(['tag-1', 'tag-2']);
        $tagger->tagResponse($response);
        $this->assertFalse($tagger->hasTags());
    }

    public function testTagResponseNoTags()
    {
        /** @var TagHeaderFormatter $headerFormatter */
        $headerFormatter = \Mockery::mock(TagHeaderFormatter::class)
            ->shouldReceive('getTagsHeaderValue')->never()
            ->getMock();

        $tagger = new ResponseTagger(['header_formatter' => $headerFormatter]);

        $response = \Mockery::mock(ResponseInterface::class)
            ->shouldReceive('withHeader')->never()
            ->shouldReceive('withAddedHeader')->never()
            ->getMock();

        $tagger->tagResponse($response, true);
    }

    public function testStrictEmptyTag()
    {
        $headerFormatter = new CommaSeparatedTagHeaderFormatter('FOS-Tags');

        $tagHandler = new ResponseTagger(['header_formatter' => $headerFormatter, 'strict' => true]);

        $this->expectException(InvalidTagException::class);
        $tagHandler->addTags(['post-1', false]);
    }

    public function testNonStrictEmptyTag()
    {
        $headerFormatter = \Mockery::mock(TagHeaderFormatter::class)
            ->shouldReceive('getTagsHeaderValue')
            ->with(['post-1'])
            ->once()
            ->andReturn('post-1')
            ->getMock();

        $tagHandler = new ResponseTagger(['header_formatter' => $headerFormatter]);
        $tagHandler->addTags(['post-1', false, null, '']);
        $this->assertTrue($tagHandler->hasTags());
        $this->assertEquals('post-1', $tagHandler->getTagsHeaderValue());
    }

    public function testUniqueTags()
    {
        $headerFormatter = new CommaSeparatedTagHeaderFormatter('FOS-Tags');

        $tagHandler = new ResponseTagger(['header_formatter' => $headerFormatter, 'strict' => true]);
        $tagHandler->addTags(['post-1', 'post-2', 'post-1']);
        $tagHandler->addTags(['post-2', 'post-3']);
        $this->assertTrue($tagHandler->hasTags());

        $this->assertEquals('post-1,post-2,post-3', $tagHandler->getTagsHeaderValue());
    }

    public function testClear()
    {
        $tagger = new ResponseTagger();
        $this->assertFalse($tagger->hasTags());
        $tagger->addTags(['foo', 'bar']);
        $this->assertTrue($tagger->hasTags());
        $tagger->clear();
        $this->assertFalse($tagger->hasTags());
    }
}
