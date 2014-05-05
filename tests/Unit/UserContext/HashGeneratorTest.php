<?php

namespace FOS\HttpCache\Tests\Unit\UserContext;

use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\HashGenerator;
use FOS\HttpCache\UserContext\UserContext;

class HashGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateHash()
    {
        $hashGenerator = new HashGenerator();
        $hashGenerator->registerProvider(new FooProvider());

        $expectedHash = hash('sha256', serialize(array('foo' => 'bar')));

        $this->assertEquals($expectedHash, $hashGenerator->generateHash());
    }
}

class FooProvider implements ContextProviderInterface
{
    public function updateUserContext(UserContext $context)
    {
        $context->addParameter('foo', 'bar');
    }
}
