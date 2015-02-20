<?php

namespace FOS\HttpCache\Tests\Unit\Test\PHPUnit;

abstract class AbstractCacheConstraintTest extends \PHPUnit_Framework_TestCase
{
    protected function getResponseMock()
    {
        $mock = \Mockery::mock(
            '\Psr\Http\Message\ResponseInterface[hasHeader,getHeaderLine,getStatusCode]'
        );

        return $mock;
    }
}
