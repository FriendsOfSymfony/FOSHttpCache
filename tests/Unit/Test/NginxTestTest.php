<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Test;

use FOS\HttpCache\Test\NginxTest;
use PHPUnit\Framework\TestCase;

class NginxTestTest extends TestCase
{
    use NginxTest;

    protected function setUp(): void
    {
        // do not try to set up proxy
    }

    protected function getBinary(): string
    {
        return '/test/binary';
    }

    protected function getCacheDir(): string
    {
        return '/tmp/foobar';
    }

    public function testGetProxy(): void
    {
        $proxy = $this->getProxy();

        $this->assertEquals('/test/binary', $proxy->getBinary());
        $this->assertEquals('/tmp/foobar', $proxy->getCacheDir());
    }
}
