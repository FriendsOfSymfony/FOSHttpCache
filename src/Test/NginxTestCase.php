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

/**
 * Abstract test that collects traits necessary for running tests against
 * NGINX.
 */
abstract class NginxTestCase extends \PHPUnit_Framework_TestCase
{
    use CacheAssertions;
    use HttpCaller;
    use NginxTest;
}
