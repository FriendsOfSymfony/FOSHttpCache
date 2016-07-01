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
