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

use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
use FOS\HttpCache\SymfonyCache\KernelDispatcher;
use GuzzleHttp\Psr7\Request as Psr7Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

/**
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class KernelDispatcherTest extends TestCase
{
    public function testFlush()
    {
        $headers = [
            'Content-Type' => 'foobar',
            'Cookie' => 'foo=bar; foscacheis=awesome',
            'X-Cache-Tags' => 'foo,bar,stuff',
        ];

        $psr7Request = new Psr7Request(
            'PURGETAGS',
            'http://127.0.0.1/foobar?query=string&more=stuff',
            $headers,
            'super content'
        );

        $httpCache = $this->createMock(HttpCache::class);
        $httpCache->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (Request $request) {
                // Test if the Symfony request contains the relevant information
                // from the PSR-7 request
                $valid = true;
                $valid = $valid && 'PURGETAGS' === $request->getMethod();
                $valid = $valid && 'foobar' === $request->headers->get('content-type');
                $valid = $valid && 'foo,bar,stuff' === $request->headers->get('x-cache-tags');
                $valid = $valid && ['query' => 'string', 'more' => 'stuff'] == $request->query->all();
                $valid = $valid && 'awesome' == $request->cookies->get('foscacheis');
                $valid = $valid && 'bar' == $request->cookies->get('foo');
                $valid = $valid && 'super content' == $request->getContent();

                return $valid;
            }))
            ->willReturn(new Response());

        $kernel = $this->createMock(HttpCacheProvider::class);
        $kernel->expects($this->once())
            ->method('getHttpCache')
            ->willReturn($httpCache);

        $dispatcher = new KernelDispatcher($kernel);
        $dispatcher->invalidate($psr7Request);

        $dispatcher->flush();
    }
}
