<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Exception;

use FOS\HttpCache\Exception\ExceptionCollection;
use PHPUnit\Framework\TestCase;

class ExceptionCollectionTest extends TestCase
{
    public function testCollectionConstructor()
    {
        $e1 = new \RuntimeException();
        $e2 = new \RuntimeException('Message');
        $collection = new ExceptionCollection([$e1, $e2]);

        $this->assertEquals('Message', $collection->getMessage());
        $this->assertCount(2, $collection);
        $this->assertSame($e1, $collection->getFirst());

        $actual = [];
        foreach ($collection as $e) {
            $actual[] = $e;
        }
        $this->assertEquals([$e1, $e2], $actual);
    }

    public function testCollectionAdd()
    {
        $collection = new ExceptionCollection();
        $this->assertNull($collection->getFirst());

        $e1 = new \RuntimeException();
        $collection->add($e1);

        $e2 = new \RuntimeException('Message');
        $collection->add($e2);
        $this->assertEquals('Message', $collection->getMessage());

        $this->assertCount(2, $collection);

        $this->assertSame($e1, $collection->getFirst());

        $actual = [];
        foreach ($collection as $e) {
            $actual[] = $e;
        }
        $this->assertEquals([$e1, $e2], $actual);
    }
}
