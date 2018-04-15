<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\SymfonyCache;

use FOS\HttpCache\SymfonyCache\HttpCacheAwareKernelInterface;
use FOS\HttpCache\SymfonyCache\KernelClient;
use GuzzleHttp\Psr7\Request as Psr7Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

/**
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class KernelClientTest extends TestCase
{
    public function testSendAsyncRequest()
    {
        $headers = [
            'Content-Type' => 'foobar',
            'Cookie' => 'foo=bar; foscacheis=awesome',
            'X-Cache-Tags' => 'foo,bar,stuff',
            'Host' => '127.0.0.1',
        ];

        $psr7Request = new Psr7Request(
            'GET',
            'http://127.0.0.1/foobar?query=string&more=stuff',
            $headers
        );

        Request::setFactory(function($query, $request, $attributes, $cookies, $files, $server, $content) {
            return new Request($query, $request, $attributes, $cookies, $files, $server, '');
        });

        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->server->set('SERVER_NAME', '127.0.0.1');
        $request->server->set('SERVER_PORT', null);
        $request->server->set('REQUEST_URI', '/foobar');
        $request->server->set('REQUEST_METHOD', 'GET');
        $request->server->set('QUERY_STRING', 'query=string&more=stuff');
        $request->headers->replace($headers);
        $request->query->replace(['query' => 'string', 'more' => 'stuff']);
        Request::setFactory(null);

        $httpCache = $this->createMock(HttpCache::class);
        $httpCache->expects($this->once())
            ->method('handle')
            ->with($request) // Tests if psr-7 request is correctly mapped to Symfony request
            ->willReturn(new Response());

        $kernel = $this->createMock(HttpCacheAwareKernelInterface::class);
        $kernel->expects($this->once())
            ->method('getHttpCache')
            ->willReturn($httpCache);

        $client = new KernelClient($kernel);
        $promise = $client->sendAsyncRequest($psr7Request);

        /** @var \Zend\Diactoros\Response $response */
        $response = $promise->wait();

        $this->assertSame(200, $response->getStatusCode());
    }
}
