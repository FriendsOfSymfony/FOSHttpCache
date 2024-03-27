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

use FOS\HttpCache\Test\PHPUnit\IsCacheMissConstraint;
use PHPUnit\Framework\ExpectationFailedException;

class IsCacheMissConstraintTestCase extends AbstractCacheConstraintTestCase
{
    private IsCacheMissConstraint $constraint;

    public function setUp(): void
    {
        $this->constraint = new IsCacheMissConstraint('cache-header');
    }

    public function testMatches(): void
    {
        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('cache-header')->andReturn(true)
            ->shouldReceive('getHeaderLine')->with('cache-header')->once()->andReturn('HIT')
            ->shouldReceive('getStatusCode')->andReturn(200)
            ->getMock();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that response (with status code 200) is a cache miss');

        $this->constraint->evaluate($response);
    }
}
