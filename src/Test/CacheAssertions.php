<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test;

use FOS\HttpCache\Test\PHPUnit\IsCacheHitConstraint;
use FOS\HttpCache\Test\PHPUnit\IsCacheMissConstraint;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides cache hit/miss assertions to PHPUnit tests.
 *
 * To enable the assertHit and assertMiss assertions, you need to configure your
 * caching proxy to set an X-Cache header with the cache status.
 *
 * Use this trait in conjunction with either the NginxTest, SymfonyTest or
 * VarnishTest trait to reset the cache between tests and properly isolate your
 * assertions.
 */
trait CacheAssertions
{
    /**
     * Assert a cache miss.
     *
     * @param string $message Test failure message (optional)
     */
    public function assertMiss(ResponseInterface $response, $message = '')
    {
        TestCase::assertThat($response, self::isCacheMiss(), $message);
    }

    /**
     * Assert a cache hit.
     *
     * @param string $message Test failure message (optional)
     */
    public function assertHit(ResponseInterface $response, $message = '')
    {
        TestCase::assertThat($response, self::isCacheHit(), $message);
    }

    public static function isCacheHit()
    {
        return new IsCacheHitConstraint();
    }

    public static function isCacheMiss()
    {
        return new IsCacheMissConstraint();
    }
}
