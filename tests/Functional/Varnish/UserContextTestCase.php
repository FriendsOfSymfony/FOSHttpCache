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

/**
 * Test case for VCL handling the user context.
 *
 * @group webserver
 * @group varnish
 */
abstract class UserContextTestCase extends VarnishTestCase
{
    /**
     * Assert that the context cache status is as expected.
     *
     * @param string $hashCache the cache status of the context request
     */
    abstract protected function assertContextCache($hashCache);

    /**
     * Sending requests without an Accept: header so none should arrive at the
     * backend for the actual request.
     */
    public function testUserContextHash()
    {
        $response1 = $this->getResponse('/user_context.php', ['Cookie' => ['0=foo']]);
        $this->assertEquals('foo', (string) $response1->getBody());
        $this->assertEquals('MISS', $response1->getHeaderLine('X-HashCache'));

        $response2 = $this->getResponse('/user_context.php', ['Cookie' => ['0=bar']]);
        $this->assertEquals('bar', (string) $response2->getBody());
        $this->assertEquals('MISS', $response2->getHeaderLine('X-HashCache'));

        $cachedResponse1 = $this->getResponse('/user_context.php', ['Cookie' => ['0=foo']]);
        $this->assertEquals('foo', (string) $cachedResponse1->getBody());
        $this->assertContextCache($cachedResponse1->getHeaderLine('X-HashCache'));
        $this->assertHit($cachedResponse1);

        $cachedResponse2 = $this->getResponse('/user_context.php', ['Cookie' => ['0=bar']]);
        $this->assertEquals('bar', $cachedResponse2->getBody());
        $this->assertContextCache($cachedResponse2->getHeaderLine('X-HashCache'));
        $this->assertHit($cachedResponse2);

        $headResponse1 = $this->getResponse('/user_context.php', ['Cookie' => ['0=foo'], [], 'HEAD']);
        $this->assertEquals('foo', $headResponse1->getHeaderLine('X-HashTest'));
        $this->assertContextCache($headResponse1->getHeaderLine('X-HashCache'));
        $this->assertHit($headResponse1);

        $headResponse2 = $this->getResponse('/user_context.php', ['Cookie' => ['0=bar'], [], 'HEAD']);
        $this->assertEquals('bar', $headResponse2->getHeaderLine('X-HashTest'));
        $this->assertContextCache($headResponse2->getHeaderLine('X-HashCache'));
        $this->assertHit($headResponse2);
    }

    /**
     * Making sure that non-authenticated and authenticated cache are not mixed up.
     */
    public function testUserContextNoAuth()
    {
        $response1 = $this->getResponse('/user_context_anon.php');
        $this->assertEquals('anonymous', $response1->getBody());
        $this->assertEquals('MISS', $response1->getHeaderLine('X-HashCache'));

        $response1 = $this->getResponse('/user_context_anon.php', ['Cookie' => ['0=foo']]);
        $this->assertEquals('foo', (string) $response1->getBody());
        $this->assertEquals('MISS', $response1->getHeaderLine('X-HashCache'));

        $cachedResponse1 = $this->getResponse('/user_context_anon.php');
        $this->assertEquals('anonymous', (string) $cachedResponse1->getBody());
        $this->assertHit($cachedResponse1);

        $cachedResponse2 = $this->getResponse('/user_context_anon.php', ['Cookie' => ['0=foo']]);
        $this->assertEquals('foo', (string) $cachedResponse2->getBody());
        $this->assertContextCache($cachedResponse2->getHeaderLine('X-HashCache'));
        $this->assertHit($cachedResponse2);
    }

    public function testAcceptHeader()
    {
        $response1 = $this->getResponse(
            '/user_context.php?accept=text/plain',
            [
                'Accept' => 'text/plain',
                'Cookie' => '0=foo',
            ]
        );
        $this->assertEquals('foo', $response1->getBody());
    }

    public function testUserContextUnauthorized()
    {
        $response = $this->getResponse('/user_context.php', ['Cookie' => ['0=miam']]);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('MISS', $response->getHeaderLine('X-HashCache'));
        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->getResponse('/user_context.php', ['Cookie' => ['0=miam']]);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertContextCache($response->getHeaderLine('X-HashCache'));
        $this->assertEquals(403, $response->getStatusCode());
    }
}
