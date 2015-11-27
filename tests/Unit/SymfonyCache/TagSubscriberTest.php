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
        $this->tagManager = $this->prophesize('FOS\HttpCache\SymfonyCache\Tag\ManagerInterface');
        $this->event = $this->prophesize('FOS\HttpCache\SymfonyCache\CacheEvent');
    }

    /**
     * If the HTTP method is not INVALIDATE it should return early.
     */
    public function testNoInvalidate()
    {
        $request = Request::create('/url');
        $this->event->getRequest()->willReturn($request);
        $this->event->setResponse(Argument::any())->shouldNotBeCalled();
        $this->createSubscriber(array())->preHandle(
            $this->event->reveal()
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
        $this->event->getRequest()->willReturn($request);
        $this->tagManager->invalidateTags(['one', 'two'])->shouldBeCalled();
        $this->event->setResponse(Argument::any())->shouldBeCalled();
        $this->createSubscriber(array())->preHandle(
            $this->event->reveal()
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
        $this->event->getRequest()->willReturn($request);
        $this->createSubscriber(array())->preHandle(
            $this->event->reveal()
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
        $this->event->getRequest()->willReturn($request);
        $this->createSubscriber(array())->preHandle(
            $this->event->reveal()
        );
    }

    /**
     * It should return early if no tags header is present.
     */
    public function testHandleTagsNoHeader()
    {
        $response = Response::create('', 200, array());

        $this->event->getResponse()->willReturn($response);
        $this->createSubscriber(array())->postHandle(
            $this->event->reveal()
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

        $this->event->getResponse()->willReturn($response);

        $this->tagManager->createTag('one', '1234')->shouldBeCalled();
        $this->tagManager->createTag('two', '1234')->shouldBeCalled();
        $this->createSubscriber(array())->postHandle(
            $this->event->reveal()
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

        $this->event->getResponse()->willReturn($response);
        $this->createSubscriber(array())->postHandle(
            $this->event->reveal()
        );
    }

    private function createSubscriber($options)
    {
        return new TagSubscriber($this->tagManager->reveal(), $options);
    }
}
