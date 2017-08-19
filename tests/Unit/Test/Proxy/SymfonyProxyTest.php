<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Test\Proxy;

use FOS\HttpCache\Test\Proxy\SymfonyProxy;
use PHPUnit\Framework\TestCase;

class SymfonyProxyTest extends TestCase
{
    public function testStart()
    {
        $proxy = new SymfonyProxy();
        $proxy->start();
        $proxy->stop();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidDirectoryThrowsException()
    {
        define('SYMFONY_CACHE_DIR', '/');
        $proxy = new SymfonyProxy();
        $proxy->getCacheDir();
    }
}
