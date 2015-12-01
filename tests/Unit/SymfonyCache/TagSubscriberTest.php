<?php

namespace FOS\HttpCache\Tests\Unit\SymfonyCache;

use Symfony\Component\HttpFoundation\Request;
use Prophecy\Argument;
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use Symfony\Component\HttpFoundation\Response;

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
        $this->tagManager = \Mockery::mock('FOS\HttpCache\SymfonyCache\Tag\ManagerInterface');
        $this->event = \Mockery::mock('FOS\HttpCache\SymfonyCache\CacheEvent');
    }

    /**
     * If the HTTP method is not INVALIDATE it should return early.
     */
    public function testNoInvalidate()
    {
        $request = Request::create('/url');
        $this->event->shouldReceive('getRequest')->andReturn($request);
        $this->event->shouldReceive('setResponse')->never();
        $this->createSubscriber(array())->preHandle(
            $this->event
        );
    }

    /**
     * It the HTTP method is INVALIDATE then it should invalidate the tags
     * given in the tags header.
     */
    public function testInvalidate()
    {
        $request = Request::create('/url', 'INVALIDATE');
        $request->headers->set('X-TaggedCache-Tags', json_encode(['one', 'two']));
        $this->event->shouldReceive('getRequest')->andReturn($request);
        $this->tagManager->shouldReceive('invalidateTags')
            ->withArgs([['one', 'two']])
            ->andReturn('asd');
        $this->event->shouldReceive('setResponse');

        $this->createSubscriber(array())->preHandle(
            $this->event
        );
    }

    /**
     * It should throw an exception if the tags header is not present but the
     * HTTP method is INVALIDATE
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not find header
     */
    public function testInvalidateNoTagsHeader()
    {
        $request = Request::create('/url', 'INVALIDATE');
        $this->event->shouldReceive('getRequest')->andReturn($request);
        $this->createSubscriber(array())->preHandle(
            $this->event
        );
    }

    /**
     * It should throw an exception if it could not decode the JSON tags from
     * the header.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not JSON decode
     */
    public function testInvalidateInvalidJson()
    {
        $request = Request::create('/url', 'INVALIDATE');
        $request->headers->set('X-TaggedCache-Tags', 'one,two');
        $this->event->shouldReceive('getRequest')->andReturn($request);
        $this->createSubscriber(array())->preHandle(
            $this->event
        );
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
            'X-TaggedCache-Tags' => json_encode(['one', 'two'])
        ]);

        $this->event->shouldReceive('getResponse')->andReturn($response);

        $this->tagManager->shouldReceive('createTag')->withArgs(['one', '1234']);
        $this->tagManager->shouldReceive('createTag')->withArgs(['two', '1234']);
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
            'X-TaggedCache-Tags' => json_encode(['one', 'two'])
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
