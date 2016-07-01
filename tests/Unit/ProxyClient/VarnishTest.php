<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\ProxyClient;

use FOS\HttpCache\ProxyClient\Varnish;
use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\Request;

class VarnishTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockPlugin
     */
    protected $mock;

    protected $client;

    public function testBanEverything()
    {
        $varnish = new Varnish(array('127.0.0.1:123'), 'fos.lo', $this->client);
        $varnish->ban(array())->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $headers = $requests[0]->getHeaders();
        $this->assertEquals('.*', $headers->get('X-Host'));
        $this->assertEquals('.*', $headers->get('X-Url'));
        $this->assertEquals('.*', $headers->get('X-Content-Type'));
        $this->assertEquals('fos.lo', $headers->get('Host'));
    }

    public function testBanEverythingNoBaseUrl()
    {
        $varnish = new Varnish(array('127.0.0.1:123'), null, $this->client);
        $varnish->ban(array())->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $headers = $requests[0]->getHeaders();
        $this->assertEquals('.*', $headers->get('X-Host'));
        $this->assertEquals('.*', $headers->get('X-Url'));
        $this->assertEquals('.*', $headers->get('X-Content-Type'));
        // Ensure host header matches the Varnish server one.
        $this->assertEquals(array('127.0.0.1:123'), $headers->get('Host')->toArray());
    }

    public function testBanHeaders()
    {
        $varnish = new Varnish(array('127.0.0.1:123'), 'fos.lo', $this->client);
        $varnish->setDefaultBanHeaders(
            array('A' => 'B')
        );
        $varnish->setDefaultBanHeader('Test', '.*');
        $varnish->ban(array())->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $headers = $requests[0]->getHeaders();
        $this->assertEquals('.*', $headers->get('Test'));
        $this->assertEquals('B', $headers->get('A'));
        $this->assertEquals('fos.lo', $headers->get('Host'));
    }

    public function testBanPath()
    {
        $varnish = new Varnish(array('127.0.0.1:123'), 'fos.lo', $this->client);

        $hosts = array('fos.lo', 'fos2.lo');
        $varnish->banPath('/articles/.*', 'text/html', $hosts)->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $headers = $requests[0]->getHeaders();
        $this->assertEquals('^(fos.lo|fos2.lo)$', $headers->get('X-Host'));
        $this->assertEquals('/articles/.*', $headers->get('X-Url'));
        $this->assertEquals('text/html', $headers->get('X-Content-Type'));
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidArgumentException
     */
    public function testBanPathEmptyHost()
    {
        $varnish = new Varnish(array('127.0.0.1:123'), 'fos.lo', $this->client);

        $hosts = array();
        $varnish->banPath('/articles/.*', 'text/html', $hosts);
    }

    public function testPurge()
    {
        $self = $this; // For PHP 5.3
        $client = \Mockery::mock('\Guzzle\Http\Client[send]', array('', null))
            ->shouldReceive('send')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) use ($self) {
                        /* @type Request[] $requests */
                        $self->assertCount(4, $requests);
                        foreach ($requests as $request) {
                            $self->assertEquals('PURGE', $request->getMethod());
                            $self->assertEquals('my_hostname.dev', $request->getHeaders()->get('host'));
                        }

                        $self->assertEquals('127.0.0.1', $requests[0]->getHost());
                        $self->assertEquals('8080', $requests[0]->getPort());
                        $self->assertEquals('/url/one', $requests[0]->getPath());

                        $self->assertEquals('123.123.123.2', $requests[1]->getHost());
                        $self->assertEquals('/url/one', $requests[1]->getPath());

                        $self->assertEquals('127.0.0.1', $requests[2]->getHost());
                        $self->assertEquals('8080', $requests[2]->getPort());
                        $self->assertEquals('/url/two', $requests[2]->getPath());
                        $self->assertEquals('bar', $requests[2]->getHeader('X-Foo'));

                        $self->assertEquals('123.123.123.2', $requests[3]->getHost());
                        $self->assertEquals('/url/two', $requests[3]->getPath());
                        $self->assertEquals('bar', $requests[3]->getHeader('X-Foo'));

                        return true;
                    }
                )
            )
            ->getMock();

        $ips = array(
            '127.0.0.1:8080',
            '123.123.123.2',
        );

        $varnish = new Varnish($ips, 'my_hostname.dev', $client);

        $varnish->purge('/url/one');
        $varnish->purge('/url/two', array('X-Foo' => 'bar'));

        $varnish->flush();
    }

    public function testRefresh()
    {
        $varnish = new Varnish(array('127.0.0.1:123'), 'fos.lo', $this->client);
        $varnish->refresh('/fresh')->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('GET', $requests[0]->getMethod());
        $this->assertEquals('http://127.0.0.1:123/fresh', $requests[0]->getUrl());
    }

    protected function setUp()
    {
        $this->mock = new MockPlugin();
        $this->mock->addResponse(new Response(200));
        $this->client = new Client();
        $this->client->addSubscriber($this->mock);
    }

    /**
     * @return array|Request[]
     */
    protected function getRequests()
    {
        return $this->mock->getReceivedRequests();
    }
}
