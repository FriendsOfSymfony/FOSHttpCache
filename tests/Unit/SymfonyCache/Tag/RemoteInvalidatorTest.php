<?php

namespace FOS\HttpCache\Tests\Unit\SymfonyCache\Tag;

use FOS\HttpCache\SymfonyCache\Tag\RemoteInvalidator;
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use FOS\HttpCache\ProxyClient\Symfony;

class RemoteInvalidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * It should return an InvalidationRequest
     */
    public function testRemoteInvalidator()
    {
        $invalidator = new RemoteInvalidator();
        $request = $invalidator->invalidateTags(['one', 'two']);

        $this->assertInstanceOf('FOS\HttpCache\ProxyClient\Request\InvalidationRequest', $request);
        $this->assertEquals(
            Symfony::HTTP_METHOD_INVALIDATE,
            $request->getMethod()
        );
        $this->assertEquals(
            [
                json_encode(['one', 'two'])
            ],
            $request->getHeader(TagSubscriber::HEADER_TAGS)
        );
    }
}
