<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\ProxyClient;

use FOS\HttpCache\ProxyClient\Noop;
use PHPUnit\Framework\TestCase;

class NoopTest extends TestCase
{
    /**
     * @var Noop
     */
    private $noop;

    protected function setUp()
    {
        $this->noop = new Noop();
    }

    public function testBan()
    {
        $this->assertSame($this->noop, $this->noop->ban(['header-123']));
    }

    public function testInvalidateTags()
    {
        $this->assertSame($this->noop, $this->noop->invalidateTags(['tag123']));
    }

    public function testBanPath()
    {
        $this->assertSame($this->noop, $this->noop->banPath('/123'));
    }

    public function testFlush()
    {
        $this->assertTrue(is_int($this->noop->flush()));
    }

    public function testPurge()
    {
        $this->assertSame($this->noop, $this->noop->purge('/123', ['x-123' => 'yes']));
    }

    public function testRefresh()
    {
        $this->assertSame($this->noop, $this->noop->refresh('/123'));
    }
}
