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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

abstract class AbstractCacheConstraintTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function getResponseMock()
    {
        $mock = \Mockery::mock(
            '\Psr\Http\Message\ResponseInterface[hasHeader,getHeaderLine,getStatusCode,getHeaders,getBody]'
        );

        return $mock;
    }
}
