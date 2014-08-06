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

class IsCacheMissConstraintTest extends AbstractCacheConstraintTest
{
    /**
     * @var IsCacheMissConstraint
     */
    private $constraint;

    public function setUp()
    {
        $this->constraint = new IsCacheMissConstraint('cache-header');
    }

    /**
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testMatches()
    {
        $response = $this->getResponseMock()
            ->shouldReceive('hasHeader')->with('cache-header')->andReturn(true)
            ->shouldReceive('getHeader')->with('cache-header')->once()
            ->andReturn('HIT')
            ->getMock();

        $this->constraint->evaluate($response);
    }
}
