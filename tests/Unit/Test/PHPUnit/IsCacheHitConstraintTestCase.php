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
use PHPUnit\Framework\ExpectationFailedException;

class IsCacheHitConstraintTestCase extends AbstractCacheConstraintTestCase
{
    private IsCacheHitConstraint $constraint;

    public function setUp(): void
    {
        $this->constraint = new IsCacheHitConstraint('cache-header');
    }

    public function testMatches(): void
    {
        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('cache-header')->andReturn(true)
            ->shouldReceive('getHeaderLine')->with('cache-header')->once()->andReturn('MISS')
            ->shouldReceive('getStatusCode')->andReturn(500)
            ->shouldReceive('getHeaders')->andReturn([])
            ->shouldReceive('getBody')->andReturn(new Stream(fopen('php://temp', 'r+')))
            ->getMock();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that response (with status code 500) is a cache hit');
        $this->constraint->evaluate($response);
    }

    public function testMatchesThrowsExceptionIfHeaderIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Response has no "cache-header" header');

        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('cache-header')->once()->andReturn(false)
            ->shouldReceive('getStatusCode')->andReturn(200)
            ->shouldReceive('getHeaders')->andReturn([])
            ->shouldReceive('getBody')->andReturn(new Stream(fopen('php://temp', 'r+')))
            ->getMock();

        $this->constraint->evaluate($response);
    }
}
