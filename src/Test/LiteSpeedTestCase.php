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

/**
 * Abstract test that collects traits necessary for running tests against
 * LiteSpeed.
 */
abstract class LiteSpeedTestCase extends TestCase
{
    use CacheAssertions;
    use HttpCaller;
    use LiteSpeedTest;

    public static function isCacheHit()
    {
        return new IsCacheHitConstraint('X-LiteSpeed-Cache');
    }

    public static function isCacheMiss()
    {
        return new IsCacheMissConstraint('X-LiteSpeed-Cache', true);
    }
}
