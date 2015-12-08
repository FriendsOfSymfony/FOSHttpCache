<?php

namespace FOS\HttpCache\Tests\Unit\SymfonyCache;

use Symfony\Component\HttpFoundation\Request;
use Prophecy\Argument;
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use Symfony\Component\HttpFoundation\Response;
use FOS\HttpCache\ProxyClient\Symfony;

class TagSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var CacheEvent
     */
    private $event;

    public function setUp()
    {
        $this->tagManager = \Mockery::mock('FOS\HttpCache\Tag\ManagerInterface');
        $this->event = \Mockery::mock('FOS\HttpCache\SymfonyCache\CacheEvent');
    }

    /**
     * It should return early if no tags header is present.
     */
    public function testHandleTagsNoHeader()
    {
        $response = Response::create('', 200, array());

        $this->event->shouldReceive('getResponse')->andReturn($response);
        $this->createSubscriber(array())->postHandle(
            $this->event
        );
    }

    /**
     * When the tags header is in the response It should call the tag manager
     * to create tags.
     */
    public function testHandleTags()
    {
        $response = Response::create('test', 200, [
            'X-Content-Digest' => '1234',
            'X-Cache-Tags' => json_encode(['one', 'two'])
        ]);
        $response->setMaxAge(10);

        $this->event->shouldReceive('getResponse')->andReturn($response);

        $this->tagManager->shouldReceive('tagCacheId')->withArgs([ ['one', 'two'], '1234', 10]);
        $this->createSubscriber(array())->postHandle(
            $this->event
        );
    }

    /**
     * It should throw an exception if the content digest header is not present.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not find content digest
     */
    public function testHandleTagsNoContentDigest()
    {
        $response = Response::create('test', 200, [
            'X-Cache-Tags' => json_encode(['one', 'two'])
        ]);

        $this->event->shouldReceive('getResponse')->andReturn($response);
        $this->createSubscriber(array())->postHandle(
            $this->event
        );
    }

    /**
     * It should throw an exception if the JSON is invalid.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not JSON decode
     */
    public function testInvalidJsonEncodedTags()
    {
        $digest = 'abcd1234';

        $response = Response::create('response', 200, [
            Symfony::HTTP_HEADER_CONTENT_DIGEST => $digest,
            Symfony::HTTP_HEADER_TAGS => 'this ain\'t JSON',
        ]);
        $this->event->shouldReceive('getResponse')->andReturn($response);
        $this->createSubscriber(array())->postHandle(
            $this->event
        );
    }

    private function createSubscriber($options)
    {
        return new TagSubscriber($this->tagManager, $options);
    }
}
