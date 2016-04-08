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
use Http\Mock\Client;
use \Mockery;
use Psr\Http\Message\RequestInterface;
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use FOS\HttpCache\ProxyClient\Request\InvalidationRequest;

class SymfonyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock client
     *
     * @var Client
     */
    protected $client;

    public function testPurge()
    {
        $ips = ['127.0.0.1:8080', '123.123.123.2'];
        $symfony = new Symfony($ips, ['base_uri' => 'my_hostname.dev'], $this->client);

        $count = $symfony->purge('/url/one')
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
        $symfony = new Symfony(['127.0.0.1:123'], ['base_uri' => 'fos.lo'], $this->client);
        $symfony->refresh('/fresh')->flush();

        $requests = $this->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('GET', $requests[0]->getMethod());
        $this->assertEquals('http://127.0.0.1:123/fresh', $requests[0]->getUri());
    }

    /**
     * It should use a NullManager by default
     */
    public function testInvalidateTagsNullManager()
    {
        $symfony = new Symfony(['127.0.0.1:123'], 'fos.lo', $this->client);
        $symfony->invalidateTags(['one']);
    }

    /**
     * It should call the tag invalidator given in the options
     */
    public function testInvalidateTags()
    {
        $invalidator = \Mockery::mock('FOS\HttpCache\SymfonyCache\Tag\InvalidatorInterface')
            ->shouldReceive('invalidateTags')
            ->withArgs([['one']])
            ->mock();

        $symfony = new Symfony(['127.0.0.1:123'], 'fos.lo', $this->client, [
            'tags_invalidator' => $invalidator
        ]);
        $symfony->invalidateTags(['one']);
    }

    /**
     * It should return the tags header name and encode the tag list
     */
    public function testTagHeader()
    {
        $symfony = new Symfony(['127.0.0.1:123'], 'fos.lo', $this->client);
        $this->assertEquals(Symfony::HTTP_HEADER_TAGS, $symfony->getTagsHeaderName());
        $this->assertEquals('["one","two"]', $symfony->getTagsHeaderValue(['one', 'two']));
    }

    protected function setUp()
    {
        $this->client = new Client();
    }

    /**
     * @return array|RequestInterface[]
     */
    protected function getRequests()
    {
        return $this->client->getRequests();
    }
}
