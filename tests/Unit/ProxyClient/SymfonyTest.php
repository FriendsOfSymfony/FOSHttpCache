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

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\ProxyClient\Symfony;
use FOS\HttpCache\ProxyClient\Varnish;
use FOS\HttpCache\Test\HttpClient\MockHttpAdapter;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\Request;
use \Mockery;

class SymfonyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockHttpAdapter
     */
    protected $client;

    public function testPurge()
    {
        $ips = ['127.0.0.1:8080', '123.123.123.2'];
        $varnish = new Varnish($ips, 'my_hostname.dev', $this->client);

        $count = $varnish->purge('/url/one')
            ->purge('/url/two', array('X-Foo' => 'bar'))
            ->flush()
        ;
        $this->assertEquals(2, $count);
        
        $requests = $this->getRequests();
        $this->assertCount(4, $requests);
        foreach ($requests as $request) {
            $this->assertEquals('PURGE', $request->getMethod());
            $this->assertEquals('my_hostname.dev', $request->getHeaderLine('Host'));
        }
    
        $this->assertEquals('http://127.0.0.1:8080/url/one', $requests[0]->getUri());
        $this->assertEquals('http://123.123.123.2/url/one', $requests[1]->getUri());
        $this->assertEquals('http://127.0.0.1:8080/url/two', $requests[2]->getUri());
        $this->assertEquals('bar', $requests[2]->getHeaderLine('X-Foo'));
        $this->assertEquals('http://123.123.123.2/url/two', $requests[3]->getUri());
        $this->assertEquals('bar', $requests[3]->getHeaderLine('X-Foo'));
    }

    public function testRefresh()
    {
        $symfony = new Symfony(array('127.0.0.1:123'), 'fos.lo', $this->client);
        $symfony->refresh('/fresh')->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('GET', $requests[0]->getMethod());
        $this->assertEquals('http://127.0.0.1:123/fresh', $requests[0]->getUri());
    }

    protected function setUp()
    {
        $this->client = new MockHttpAdapter();
    }
    
    /**
     * @return array|RequestInterface[]
     */
    protected function getRequests()
    {
        return $this->client->getRequests();
    }
}
