<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\UserContext;

use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\HashGenerator;
use FOS\HttpCache\UserContext\UserContext;

class HashGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateHash()
    {
        $hashGenerator = new HashGenerator(array(new FooProvider()));

        $expectedHash = hash('sha256', serialize(array('foo' => 'bar')));

        $this->assertEquals($expectedHash, $hashGenerator->generateHash());
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidArgumentException
     */
    public function testConstructorError()
    {
        new HashGenerator(array());
    }
}

class FooProvider implements ContextProviderInterface
{
    public function updateUserContext(UserContext $context)
    {
        $context->addParameter('foo', 'bar');
    }
}
