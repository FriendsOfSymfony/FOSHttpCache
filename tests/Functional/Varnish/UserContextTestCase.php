<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\Varnish;

use FOS\HttpCache\Test\VarnishTestCase;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * @group webserver
 * @group varnish
 */
abstract class UserContextTestCase extends VarnishTestCase
{
    /**
     * Assert that the context cache status is as expected.
     *
     * @param string $hashCache The cache status of the context request.
     */
    abstract protected function assertContextCache($hashCache);

    public function testUserContextHash()
    {
        $response1 = $this->getResponse('/user_context.php', array(), array('cookies' => array('foo')));
        $this->assertEquals('foo', $response1->getBody(true));
        $this->assertEquals('MISS', $response1->getHeader('X-HashCache'));

        $response2 = $this->getResponse('/user_context.php', array(), array('cookies' => array('bar')));
        $this->assertEquals('bar', $response2->getBody(true));
        $this->assertEquals('MISS', $response2->getHeader('X-HashCache'));

        $cachedResponse1 = $this->getResponse('/user_context.php', array(), array('cookies' => array('foo')));
        $this->assertEquals('foo', $cachedResponse1->getBody(true));
        $this->assertContextCache($cachedResponse1->getHeader('X-HashCache'));
        $this->assertHit($cachedResponse1);

        $cachedResponse2 = $this->getResponse('/user_context.php', array(), array('cookies' => array('bar')));
        $this->assertEquals('bar', $cachedResponse2->getBody(true));
        $this->assertContextCache($cachedResponse2->getHeader('X-HashCache'));
        $this->assertHit($cachedResponse2);

        $headResponse1 = $this->getHttpClient()
            ->head('/user_context.php', array(), array('cookies' => array('foo')))
            ->send();

        $this->assertEquals('foo', $headResponse1->getHeader('X-HashTest'));
        $this->assertContextCache($headResponse1->getHeader('X-HashCache'));
        $this->assertHit($headResponse1);

        $headResponse2 = $this->getHttpClient()
            ->head('/user_context.php', array(), array('cookies' => array('bar')))
            ->send();

        $this->assertEquals('bar', $headResponse2->getHeader('X-HashTest'));
        $this->assertContextCache($headResponse2->getHeader('X-HashCache'));
        $this->assertHit($headResponse2);
    }

    public function testUserContextUnauthorized()
    {
        try {
            $this->getResponse('/user_context.php', array(), array('cookies' => array('miam')));

            $this->fail('Request should have failed with a 403 response');
        } catch (ClientErrorResponseException $e) {
            $this->assertEquals('MISS', $e->getResponse()->getHeader('X-HashCache'));
            $this->assertEquals(403, $e->getResponse()->getStatusCode());
        }

        try {
            $this->getResponse('/user_context.php', array(), array('cookies' => array('miam')));

            $this->fail('Request should have failed with a 403 response');
        } catch (ClientErrorResponseException $e) {
            $this->assertContextCache($e->getResponse()->getHeader('X-HashCache'));
            $this->assertEquals(403, $e->getResponse()->getStatusCode());
        }
    }
}
