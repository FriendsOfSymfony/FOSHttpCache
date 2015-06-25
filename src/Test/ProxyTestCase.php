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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Abstract test that provides an application HTTP client and cache assertions
 */
abstract class ProxyTestCase extends \PHPUnit_Framework_TestCase
{
    use HttpCaller;
    use CacheAssertions;
}
