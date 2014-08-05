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

use FOS\HttpCache\UserContext\UserContext;

class UserContextTest extends \PHPUnit_Framework_TestCase
{
    public function testAddParameter()
    {
        $userContext = new UserContext();
        $userContext->addParameter('authenticated', true);

        $this->assertTrue($userContext->hasParameter('authenticated'));

        $parameters = $userContext->getParameters();

        $this->assertEquals(true, $parameters['authenticated']);
    }

    public function testSetParameters()
    {
        $userContext = new UserContext();

        $userContext->addParameter('authenticated', true);
        $userContext->setParameters(array(
            'roles' => array('ROLE_USER'),
            'foo' => 'bar'
        ));

        $this->assertFalse($userContext->hasParameter('authenticated'));
        $this->assertTrue($userContext->hasParameter('foo'));
        $this->assertTrue($userContext->hasParameter('roles'));

        $parameters = array();
        foreach ($userContext as $name => $value) {
            $parameters[$name] = $value;
        }
        $this->assertEquals(
            array('roles' => array('ROLE_USER'), 'foo' => 'bar'),
            $parameters
        );
    }
}
