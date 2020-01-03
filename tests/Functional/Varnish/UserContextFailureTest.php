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
 * Test edge conditions and attacks.
 *
 * @group webserver
 * @group varnish
 */
class UserContextFailureTest extends VarnishTestCase
{
    /**
     * Can be "cache" or "failure" and is used to determine the correct .vcl file.
     *
     * @var string
     */
    private $mode = 'cache';

    public function setUp(): void
    {
        // needs to be decided before doing the setup
        $this->mode = 'testHashRequestFailure' === $this->getName() ? 'failure' : 'cache';

        parent::setUp();
    }

    /**
     * The user hash must not be exposed to the client under any circumstances.
     */
    public function testUserContextNoExposeHash()
    {
        $response = $this->getResponse(
            '/user_context_hash_nocache.php',
            [
                'Accept' => 'application/vnd.fos.user-context-hash',
                'Cookie' => ['0=miam'],
            ]
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('X-User-Context-Hash'));
    }

    /**
     * A hash sent by the client must not be used by varnish.
     */
    public function testUserContextNoForgedHash()
    {
        $response = $this->getResponse(
            '/user_context_hash_nocache.php',
            [
                'X-User-Context-Hash' => 'miam',
                'Cookie' => ['0=miam'],
            ]
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * A request on POST should not use the context.
     */
    public function testUserContextNotUsed()
    {
        // First request in GET
        $this->getResponse('/user_context.php', ['Cookie' => '0=foo']);

        // Second request in HEAD or POST
        $postResponse = $this->getResponse(
            '/user_context.php',
            ['Cookie' => '0=foo'],
            'POST'
        );

        $this->assertEquals('POST', $postResponse->getBody());
        $this->assertEquals('MISS', $postResponse->getHeaderLine('X-HashCache'));
        $this->assertMiss($postResponse);
    }

    public function testHashRequestFailure()
    {
        $response = $this->getResponse('/user_context.php', ['Cookie' => '0=foo']);
        $this->assertEquals(503, $response->getStatusCode());
    }

    protected function getConfigFile()
    {
        switch ((int) $this->getVarnishVersion()) {
            case 3:
                return sprintf('./tests/Functional/Fixtures/varnish-3/user_context_%s.vcl', $this->mode);
            default:
                return sprintf('./tests/Functional/Fixtures/varnish/user_context_%s.vcl', $this->mode);
        }
    }
}
