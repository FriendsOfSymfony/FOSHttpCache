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

use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCache\UserContext\DefaultHashGenerator;
use FOS\HttpCache\UserContext\UserContext;
use PHPUnit\Framework\TestCase;

class HashGeneratorTest extends TestCase
{
    public function testGenerateHash()
    {
        $hashGenerator = new DefaultHashGenerator([new FooProvider()]);

        $expectedHash = hash('sha256', serialize(['foo' => 'bar']));

        $this->assertEquals($expectedHash, $hashGenerator->generateHash());
    }

    public function testConstructorError()
    {
        $this->expectException(InvalidArgumentException::class);
        new DefaultHashGenerator([]);
    }
}

class FooProvider implements ContextProvider
{
    public function updateUserContext(UserContext $context)
    {
        $context->addParameter('foo', 'bar');
    }
}
