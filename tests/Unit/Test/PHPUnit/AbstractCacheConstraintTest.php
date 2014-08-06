<?php

namespace FOS\HttpCache\Tests\Unit\Test\PHPUnit;

abstract class AbstractCacheConstraintTest extends \PHPUnit_Framework_TestCase
{
    protected function getResponseMock()
    {
        return \Mockery::mock(
            '\Guzzle\Http\Message\Response[hasHeader,getHeader]',
            array(null)
        );
    }
}
