<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\SymfonyCache;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class CleanupCacheTagsListenerTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $this->assertEquals([
            Events::POST_HANDLE => 'removeTagsHeader',
        ], CleanupCacheTagsListener::getSubscribedEvents());
    }

    public function testNoResponse(): void
    {
        $listener = new CleanupCacheTagsListener();
        $listener->removeTagsHeader($this->createEvent());
        $this->addToAssertionCount(1); // Nothing should happen, just asserting the "response is null" case
    }

    public function testResponseHeaderIsCleanedUp(): void
    {
        // Default cache tags header
        $response = new Response();
        $response->headers->set('X-Cache-Tags', 'foo, bar');

        $listener = new CleanupCacheTagsListener();
        $listener->removeTagsHeader($this->createEvent($response));

        $this->assertFalse($response->headers->has('X-Cache-Tags'));

        // Custom cache tags header
        $response = new Response();
        $response->headers->set('Foobar', 'foo, bar');

        $listener = new CleanupCacheTagsListener('Foobar');
        $listener->removeTagsHeader($this->createEvent($response));

        $this->assertFalse($response->headers->has('Foobar'));
    }

    private function createEvent(?Response $response = null): CacheEvent
    {
        return new CacheEvent(
            $this->createMock(CacheInvalidation::class),
            new Request(),
            $response
        );
    }
}
