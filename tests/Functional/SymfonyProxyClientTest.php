<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional;

use FOS\HttpCache\ProxyClient\Symfony;
use FOS\HttpCache\Test\SymfonyTestCase;

/**
 * @group webserver
 * @group symfony
 */
class SymfonyProxyClientTest extends SymfonyTestCase
{
    public function setUp()
    {
        /*
         * We did not figure out how to run this test with hhvm. Help welcome!
         * On travis, connection is closed after the headers are sent without providing
         * anything in either the hhvm or apache log.
         */
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('Test not working with hhvm as backend server');
        }

        parent::setUp();
    }

    public function testPurge()
    {
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
        $this->assertHit($this->getResponse('/symfony.php/cache'));

        $this->getProxyClient()->purge('/symfony.php/cache')->flush();
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
    }

    public function testPurgeContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $response = $this->getResponse('/symfony.php/negotiation', $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getContentType());
        $this->assertHit($this->getResponse('/symfony.php/negotiation', $json));

        $response = $this->getResponse('/symfony.php/negotiation', $html);
        $this->assertContains('text/html', $response->getContentType());
        $this->assertMiss($response);
        $this->assertHit($this->getResponse('/symfony.php/negotiation', $html));

        $this->getResponse('/symfony.php/negotiation');
        $this->getProxyClient()->purge('/symfony.php/negotiation')->flush();
        $this->assertMiss($this->getResponse('/symfony.php/negotiation', $json));
        $this->assertMiss($this->getResponse('/symfony.php/negotiation', $html));
    }

    public function testPurgeHost()
    {
        $symfony = new Symfony(array('http://127.0.0.1:' . $this->getCachingProxyPort()), null, null, array('purge_method' => 'NOTIFY'));

        $this->getResponse('/symfony.php/cache');

        $symfony->purge('http://localhost:8080/symfony.php/cache')->flush();
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse('/symfony.php/cache'));
        $response = $this->getResponse('/symfony.php/cache');
        $this->assertHit($response);

        $this->getProxyClient()->refresh('/symfony.php/cache')->flush();
        usleep(100);
        $refreshed = $this->getResponse('/symfony.php/cache');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }

    public function testRefreshContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $this->getProxyClient()->refresh('/symfony.php/negotiation', $json)->flush();

        $this->assertHit($this->getResponse('/symfony.php/negotiation', $json));
        $this->assertMiss($this->getResponse('/symfony.php/negotiation', $html));
    }
}
