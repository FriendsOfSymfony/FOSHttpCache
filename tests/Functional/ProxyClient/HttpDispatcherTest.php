<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\ProxyClient;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\ProxyClient\HttpDispatcher;
use Http\Discovery\MessageFactoryDiscovery;

class HttpDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testNetworkError()
    {
        $requestFactory = MessageFactoryDiscovery::find();
        $dispatcher = new HttpDispatcher(['localhost:1']);
        $dispatcher->invalidate($requestFactory->createRequest('GET', 'http://fos.test/foobar'));

        try {
            $dispatcher->flush();
        } catch (ExceptionCollection $e) {
            $e = $e->getFirst();
            $this->assertInstanceOf(ProxyUnreachableException::class, $e);
        }
    }

    public function testClientError()
    {
        $requestFactory = MessageFactoryDiscovery::find();
        $dispatcher = new HttpDispatcher(['http://foshttpcache.readthedocs.io']);
        $dispatcher->invalidate($requestFactory->createRequest('GET', 'http://fos.test/this-url-should-not-exist'));

        try {
            $dispatcher->flush();
        } catch (ExceptionCollection $e) {
            $e = $e->getFirst();
            $this->assertInstanceOf(ProxyResponseException::class, $e);
        }
    }
}
