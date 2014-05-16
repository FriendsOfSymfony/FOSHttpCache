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

use FOS\HttpCache\ProxyClient\Varnish;
use FOS\HttpCache\Tests\VarnishTestCase;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * @group webserver
 */
class VarnishTest extends VarnishTestCase
{
    public function testBanAll()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->assertMiss($this->getResponse('/json.php'));
        $this->assertHit($this->getResponse('/json.php'));

        $this->getVarnish()->ban(array(Varnish::HTTP_HEADER_URL => '.*'))->flush();

        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertMiss($this->getResponse('/json.php'));
    }

    public function testBanHost()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getVarnish()->ban(array(Varnish::HTTP_HEADER_HOST => 'wrong-host.lo'))->flush();
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getVarnish()->ban(array(Varnish::HTTP_HEADER_HOST => $this->getHostname()))->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testBanPathAll()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->assertMiss($this->getResponse('/json.php'));
        $this->assertHit($this->getResponse('/json.php'));

        $this->getVarnish()->banPath('.*')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertMiss($this->getResponse('/json.php'));
    }

    public function testBanPathContentType()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->assertMiss($this->getResponse('/json.php'));
        $this->assertHit($this->getResponse('/json.php'));

        $this->getVarnish()->banPath('.*', 'text/html')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/json.php'));
    }

    public function testPurge()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $this->assertHit($this->getResponse('/cache.php'));

        $this->getVarnish()->purge('/cache.php')->flush();
        $this->assertMiss($this->getResponse('/cache.php'));
    }

    public function testPurgeContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $response = $this->getResponse('/negotation.php', $json);
        $this->assertMiss($response);
        $this->assertEquals('application/json', $response->getContentType());
        $this->assertHit($this->getResponse('/negotation.php', $json));

        $response = $this->getResponse('/negotation.php', $html);
        $this->assertContains('text/html', $response->getContentType());
        $this->assertMiss($response);
        $this->assertHit($this->getResponse('/negotation.php', $html));

        self::getResponse('/negotation.php');
        $this->getVarnish()->purge('/negotation.php')->flush();
        $this->assertMiss($this->getResponse('/negotation.php', $json));
        $this->assertMiss($this->getResponse('/negotation.php', $html));
    }

    public function testPurgeHost()
    {
        $varnish = new Varnish(array('http://127.0.0.1:' . $this->getCachingProxyPort()));

        self::getResponse('/cache.php');

        $varnish->purge('http://localhost:6181/cache.php')->flush();
        $this->assertMiss(self::getResponse('/cache.php'));
    }

    public function testRefresh()
    {
        $this->assertMiss($this->getResponse('/cache.php'));
        $response = $this->getResponse('/cache.php');
        $this->assertHit($response);

        $this->getVarnish()->refresh('/cache.php')->flush();
        usleep(1000);
        $refreshed = $this->getResponse('/cache.php');
        $this->assertGreaterThan((float) $response->getBody(true), (float) $refreshed->getBody(true));
    }

    public function testRefreshContentType()
    {
        $json = array('Accept' => 'application/json');
        $html = array('Accept' => 'text/html');

        $this->getVarnish()->refresh('/negotation.php', $json)->flush();

        $this->assertHit($this->getResponse('/negotation.php', $json));
        $this->assertMiss($this->getResponse('/negotation.php', $html));
    }

    public function testUserContext()
    {
        $response1 = $this->getResponse('/user_context.php', array(), array('cookies' => array('foo')));
        $this->assertEquals('foo', $response1->getBody(true));
        $this->assertEquals("MISS", $response1->getHeader("X-HeadCache")->__toString());

        $response2 = $this->getResponse('/user_context.php', array(), array('cookies' => array('bar')));
        $this->assertEquals('bar', $response2->getBody(true));
        $this->assertEquals("MISS", $response1->getHeader("X-HeadCache")->__toString());

        $cachedResponse1 = $this->getResponse('/user_context.php', array(), array('cookies' => array('foo')));
        $this->assertEquals('foo', $cachedResponse1->getBody(true));
        $this->assertEquals("HIT", $cachedResponse1->getHeader("X-HeadCache")->__toString());
        $this->assertHit($cachedResponse1);

        $cachedResponse2 = $this->getResponse('/user_context.php', array(), array('cookies' => array('bar')));
        $this->assertEquals('bar', $cachedResponse2->getBody(true));
        $this->assertEquals("HIT", $cachedResponse2->getHeader("X-HeadCache")->__toString());
        $this->assertHit($cachedResponse2);

        $headResponse1 = $this->getClient()->head('/user_context.php', array(), array('cookies' => array('foo')))->send();

        $this->assertEquals('foo', $headResponse1->getHeader("X-HeadTest")->__toString());
        $this->assertEquals("HIT", $headResponse1->getHeader("X-HeadCache")->__toString());
        $this->assertHit($headResponse1);

        $headResponse2 = $this->getClient()->head('/user_context.php', array(), array('cookies' => array('bar')))->send();

        $this->assertEquals('bar', $headResponse2->getHeader("X-HeadTest")->__toString());
        $this->assertEquals("HIT", $headResponse2->getHeader("X-HeadCache")->__toString());
        $this->assertHit($headResponse2);

    }

    public function testUserContextUnauthorize()
    {
        try {
            $this->getResponse('/user_context.php', array(), array('cookies' => array('miam')));

            $this->fail('Response should return a 403');
        } catch (ClientErrorResponseException $e) {
            $this->assertEquals("MISS", $e->getResponse()->getHeader("X-HeadCache")->__toString());
            $this->assertEquals("403", $e->getResponse()->getStatusCode());
        }

        try {
            $this->getResponse('/user_context.php', array(), array('cookies' => array('miam')));

            $this->fail('Response should return a 403');
        } catch (ClientErrorResponseException $e) {
            $this->assertEquals("HIT", $e->getResponse()->getHeader("X-HeadCache")->__toString());
            $this->assertEquals("403", $e->getResponse()->getStatusCode());
        }
    }

    public function testUserContextNotUsed()
    {
        //First request in get
        $this->getResponse('/user_context.php', array(), array('cookies' => array('foo')));

        //Second request in head or post
        $postResponse = $this->getClient()->post('/user_context.php', array(), null, array('cookies' => array('foo')))->send();

        $this->assertEquals('POST', $postResponse->getBody(true));
        $this->assertEquals("MISS", $postResponse->getHeader("X-HeadCache")->__toString());
        $this->assertMiss($postResponse);
    }
}
