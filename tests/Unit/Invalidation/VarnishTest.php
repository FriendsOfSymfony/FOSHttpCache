<?php

namespace FOS\HttpCache\Tests\Unit\Invalidation;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Invalidation\Varnish;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\Request;
use \Mockery;

class VarnishTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockPlugin
     */
    protected $mock;

    protected $client;

    public function testBanEverything()
    {
        $varnish = new Varnish(array('http://127.0.0.1:123'), 'fos.lo', $this->client);
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

    public function testBanPath()
    {
        $varnish = new Varnish(array('http://127.0.0.1:123'), 'fos.lo', $this->client);

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

    public function testPurge()
    {
        $self = $this; // For PHP 5.3
        $client = \Mockery::mock('\Guzzle\Http\Client[send]', array('', null))
            ->shouldReceive('send')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) use ($self) {
                        /** @type Request[] $requests */
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

                        $self->assertEquals('123.123.123.2', $requests[3]->getHost());
                        $self->assertEquals('/url/two', $requests[3]->getPath());

                        return true;
                    }
                )
            )
            ->getMock();

        $ips = array(
            'http://127.0.0.1:8080',
            'http://123.123.123.2',
        );

        $varnish = new Varnish($ips, 'my_hostname.dev', $client);

        $varnish->purge('/url/one');
        $varnish->purge('/url/two');

        $varnish->flush();
    }

    public function testRefresh()
    {
        $varnish = new Varnish(array('http://127.0.0.1:123'), 'fos.lo', $this->client);
        $varnish->refresh('/fresh')->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('GET', $requests[0]->getMethod());
        $this->assertEquals('http://127.0.0.1:123/fresh', $requests[0]->getUrl());
    }

    public function testUnreachableException()
    {
        $mock = new MockPlugin();
        $mock->addException(new CurlException('connect to host'));

        $client = new Client();
        $client->addSubscriber($mock);

        $varnish = new Varnish(array('http://127.0.0.1:123'), 'my_hostname.dev', $client);

        try {
            $varnish->purge('/paths')->flush();
        } catch (ExceptionCollection $exceptions) {
            $this->assertCount(1, $exceptions);
            $this->assertInstanceOf('\FOS\HttpCache\Exception\ProxyUnreachableException', $exceptions->getFirst());
        }

        $mock->clearQueue();
        $mock->addResponse(new Response(200));

        // Queue must now be empty, so exception above must not be thrown again.
        $varnish->purge('/path')->flush();
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\MissingHostException
     * @expectedExceptionMessage cannot be invalidated without a host
     */
    public function testMissingHostExceptionIsThrown()
    {
        $varnish = new Varnish(array('http://127.0.0.1:123'), null, $this->client);
        $varnish->purge('/path/without/hostname');
    }

    public function testSetBasePathWithHost()
    {
        $varnish = new Varnish(array('127.0.0.1'), 'fos.lo', $this->client);
        $varnish->purge('/path')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeader('Host'));
    }

    public function testSetBasePathWithPath()
    {
        $varnish = new Varnish(array('127.0.0.1'), 'http://fos.lo/my/path', $this->client);
        $varnish->purge('append')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('fos.lo', $requests[0]->getHeader('Host'));
        $this->assertEquals('http://127.0.0.1/my/path/append', $requests[0]->getUrl());
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     */
    public function testSetBasePathThrowsInvalidUrlSchemeException()
    {
        new Varnish(array('127.0.0.1'), 'https://fos.lo/my/path');
    }

    public function testSetServersDefaultSchemeIsAdded()
    {
        $varnish = new Varnish(array('127.0.0.1'), 'fos.lo', $this->client);
        $varnish->purge('/some/path')->flush();
        $requests = $this->getRequests();
        $this->assertEquals('http://127.0.0.1/some/path', $requests[0]->getUrl());
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage Host "https://127.0.0.1" with scheme "https" is invalid
     */
    public function testSetServersThrowsInvalidUrlSchemeException()
    {
        new Varnish(array('https://127.0.0.1'));
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidUrlException
     * @expectedExceptionMessage Server "http://127.0.0.1:80/some/weird/path" is invalid. Only scheme, host, port URL parts are allowed
     */
    public function testSetServersThrowsInvalidServerException()
    {
        new Varnish(array('http://127.0.0.1:80/some/weird/path'));
    }

    public function testFlushCountSuccess()
    {
        $self = $this;
        $client = \Mockery::mock('\Guzzle\Http\Client[send]', array('', null))
            ->shouldReceive('send')
            ->once()
            ->with(
                \Mockery::on(
                    function ($requests) use ($self) {
                        /** @type Request[] $requests */
                        $self->assertCount(4, $requests);
                        foreach ($requests as $request) {
                            $self->assertEquals('PURGE', $request->getMethod());
                        }

                        return true;
                    }
                )
            )
            ->getMock();

        $varnish = new Varnish(array('127.0.0.1', '127.0.0.2'), 'fos.lo', $client);

        $this->assertEquals(
            2,
            $varnish
                ->purge('/c')
                ->purge('/b')
                ->flush()
        );
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
