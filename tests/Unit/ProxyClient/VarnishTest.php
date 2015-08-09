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
use FOS\HttpCache\Test\HttpClient\MockHttpAdapter;
use Psr\Http\Message\RequestInterface;
use \Mockery;

class VarnishTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockHttpAdapter
     */
    protected $client;

    public function testBanEverything()
    {
        $varnish = new Varnish(['127.0.0.1:123'], 'fos.lo', $this->client);
        $varnish->ban([])->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $this->assertEquals('.*', $requests[0]->getHeaderLine('X-Host'));
        $this->assertEquals('.*', $requests[0]->getHeaderLine('X-Url'));
        $this->assertEquals('.*', $requests[0]->getHeaderLine('X-Content-Type'));
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
    }

    public function testBanEverythingNoBaseUrl()
    {
        $varnish = new Varnish(['127.0.0.1:123'], null, $this->client);
        $varnish->ban([])->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $this->assertEquals('.*', $requests[0]->getHeaderLine('X-Host'));
        $this->assertEquals('.*', $requests[0]->getHeaderLine('X-Url'));
        $this->assertEquals('.*', $requests[0]->getHeaderLine('X-Content-Type'));

        // Ensure host header matches the Varnish server one.
        $this->assertEquals('http://127.0.0.1:123/', $requests[0]->getUri());
    }

    public function testBanHeaders()
    {
        $varnish = new Varnish(['127.0.0.1:123'], 'fos.lo', $this->client);
        $varnish->setDefaultBanHeaders(
            ['A' => 'B']
        );
        $varnish->setDefaultBanHeader('Test', '.*');
        $varnish->ban([])->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $this->assertEquals('.*', $requests[0]->getHeaderLine('Test'));
        $this->assertEquals('B', $requests[0]->getHeaderLine('A'));
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
    }

    public function testBanPath()
    {
        $varnish = new Varnish(['127.0.0.1:123'], 'fos.lo', $this->client);

        $hosts = ['fos.lo', 'fos2.lo'];
        $varnish->banPath('/articles/.*', 'text/html', $hosts)->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $this->assertEquals('^(fos.lo|fos2.lo)$', $requests[0]->getHeaderLine('X-Host'));
        $this->assertEquals('/articles/.*', $requests[0]->getHeaderLine('X-Url'));
        $this->assertEquals('text/html', $requests[0]->getHeaderLine('X-Content-Type'));
    }

    /**
     * @expectedException \FOS\HttpCache\Exception\InvalidArgumentException
     */
    public function testBanPathEmptyHost()
    {
        $varnish = new Varnish(['127.0.0.1:123'], 'fos.lo', $this->client);

        $hosts = [];
        $varnish->banPath('/articles/.*', 'text/html', $hosts);
    }

    public function testTagsHeaders()
    {
        $varnish = new Varnish(['127.0.0.1:123'], 'fos.lo', $this->client);
        $varnish->setDefaultBanHeaders(
            ['A' => 'B']
        );
        $varnish->setDefaultBanHeader('Test', '.*');
        $varnish->invalidateTags(['post-1', 'post-type-3'])->flush();

        $requests = $this->getRequests();

        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $this->assertEquals('(post\-1|post\-type\-3)(,.+)?$', $requests[0]->getHeaderLine('X-Cache-Tags'));
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));

        // That default BANs is taken into account also for tags as they are powered by BAN in this client.
        $this->assertEquals('.*', $requests[0]->getHeaderLine('Test'));
        $this->assertEquals('B', $requests[0]->getHeaderLine('A'));
    }


    public function testTagsHeadersEscapingAndCustomHeader()
    {
        $varnish = new Varnish(['127.0.0.1:123'], 'fos.lo', $this->client, 'X-Tags-TRex');
        $varnish->invalidateTags(['post-1', 'post,type-3'])->flush();

        $requests = $this->getRequests();

        $this->assertCount(1, $requests);
        $this->assertEquals('BAN', $requests[0]->getMethod());

        $this->assertEquals('(post\-1|post_type\-3)(,.+)?$', $requests[0]->getHeaderLine('X-Tags-TRex'));
        $this->assertEquals('fos.lo', $requests[0]->getHeaderLine('Host'));
    }

    public function testPurge()
    {
        $ips = ['127.0.0.1:8080', '123.123.123.2'];
        $varnish = new Varnish($ips, 'my_hostname.dev', $this->client);

        $count = $varnish->purge('/url/one')
            ->purge('/url/two', ['X-Foo' => 'bar'])
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
        $varnish = new Varnish(['127.0.0.1:123'], 'fos.lo', $this->client);
        $varnish->refresh('/fresh')->flush();

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
