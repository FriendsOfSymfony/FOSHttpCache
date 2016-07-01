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

use FOS\HttpCache\ProxyClient\Symfony;
use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\Request;

class SymfonyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockPlugin
     */
    private $mock;

    private $client;

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

        $symfony = new Symfony($ips, 'my_hostname.dev', $client);

        $symfony->purge('/url/one');
        $symfony->purge('/url/two', array('X-Foo' => 'bar'));

        $symfony->flush();
    }

    public function testRefresh()
    {
        $symfony = new Symfony(array('127.0.0.1:123'), 'fos.lo', $this->client);
        $symfony->refresh('/fresh')->flush();

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
