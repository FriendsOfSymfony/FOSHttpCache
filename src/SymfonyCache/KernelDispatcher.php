<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\SymfonyCache;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\ProxyClient\Dispatcher;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * This Dispatcher directly calls the Symfony HttpCache kernel without
 * executing any actual HTTP requests.
 *
 * This dispatcher can only be used if your application runs on one single
 * server.
 *
 * If you use Varnish/Nginx or have multiple servers, this client can not be
 * used.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class KernelDispatcher implements Dispatcher
{
    /**
     * @var HttpCacheProvider
     */
    private $httpCacheProvider;

    /**
     * @var array
     */
    private $queue = [];

    public function __construct(HttpCacheProvider $httpCacheProvider)
    {
        $this->httpCacheProvider = $httpCacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(RequestInterface $invalidationRequest, $validateHost = true)
    {
        $request = Request::create(
            $invalidationRequest->getUri(),
            $invalidationRequest->getMethod(),
            [],
            [],
            [],
            ['REMOTE_ADDR' => '127.0.0.1'],
            $invalidationRequest->getBody()->getContents()
        );

        // Add headers
        $headers = $invalidationRequest->getHeaders();
        foreach ($headers as $name => $values) {
            $name = strtolower($name);

            // symfony won't look at the cookies header but parses it when creating the request
            if ('cookie' === $name) {
                foreach ($values as $value) {
                    foreach (explode(';', $value) as $cookieString) {
                        $chunks = explode('=', $cookieString, 2);
                        $request->cookies->add([trim($chunks[0]) => trim($chunks[1])]);
                    }
                }

                continue;
            }

            $request->headers->set($name, $values);
        }

        $this->queue[sha1($request)] = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (!count($this->queue)) {
            return 0;
        }
        $queue = $this->queue;
        $this->queue = [];

        $exceptions = new ExceptionCollection();
        $httpCache = $this->httpCacheProvider->getHttpCache();
        if (null === $httpCache) {
            throw new ProxyUnreachableException('Kernel did not return a HttpCache instance. Did you forget $kernel->setHttpCache($cacheKernel) in your front controller?');
        }

        foreach ($queue as $request) {
            try {
                $httpCache->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
            } catch (\Exception $e) {
                $exceptions->add($e);
            }
        }

        if (count($exceptions)) {
            throw $exceptions;
        }

        return count($queue);
    }
}
