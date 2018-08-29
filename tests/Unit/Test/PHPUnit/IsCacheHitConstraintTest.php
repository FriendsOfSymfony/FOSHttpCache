<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Test\PHPUnit;

use FOS\HttpCache\Test\PHPUnit\IsCacheHitConstraint;
use GuzzleHttp\Psr7\Stream;

// phpunit 5 has forward compatibility classes but missed this one
if (!class_exists('\PHPUnit\Framework\ExpectationFailedException')) {
    class_alias('\PHPUnit_Framework_ExpectationFailedException', '\PHPUnit\Framework\ExpectationFailedException');
}

class IsCacheHitConstraintTest extends AbstractCacheConstraintTest
{
    /**
     * @var IsCacheHitConstraint
     */
    private $constraint;

    public function setUp()
    {
        $this->constraint = new IsCacheHitConstraint('cache-header');
    }

    /**
     * @expectedException \PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage Failed asserting that response (with status code 500) is a cache hit
     */
    public function testMatches()
    {
        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('cache-header')->andReturn(true)
            ->shouldReceive('getHeaderLine')->with('cache-header')->once()->andReturn('MISS')
            ->shouldReceive('getStatusCode')->andReturn(500)
            ->shouldReceive('getHeaders')->andReturn([])
            ->shouldReceive('getBody')->andReturn(new Stream(fopen('php://temp', 'r+')))
            ->getMock();

        $this->constraint->evaluate($response);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Response has no "cache-header" header
     */
    public function testMatchesThrowsExceptionIfHeaderIsMissing()
    {
        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('cache-header')->once()->andReturn(false)
            ->shouldReceive('getStatusCode')->andReturn(200)
            ->shouldReceive('getHeaders')->andReturn([])
            ->shouldReceive('getBody')->andReturn(new Stream(fopen('php://temp', 'r+')))
            ->getMock();

        $this->constraint->evaluate($response);
    }
}
