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

use FOS\HttpCache\Exception\ExceptionCollection;

class ExceptionCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCollection()
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

        $actual = array();
        foreach ($collection as $e) {
            $actual[] = $e;
        }
        $this->assertEquals(array($e1, $e2), $actual);
    }
}
