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
use FOS\HttpCache\ProxyClient\Dispatcher;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * An implementation of Dispatcher that allows direct calling of the
 * Symfony HttpCache kernel without executing a real HTTP request.
 * It can only be used if you have a single node setup of Symfony and serves
 * as kind of a shortcut for easier configuration.
 * If you use Varnish or have a multiple node Symfony setup, this client is entirely
 * useless and cannot be used.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class KernelDispatcher implements Dispatcher
{
    /**
     * @var HttpCacheProvider
     */
    private $kernel;

    /**
     * @var array
     */
    private $queue = [];

    /**
     * KernelClient constructor.
     *
     * @param HttpCacheProvider $kernel
     */
    public function __construct(HttpCacheProvider $kernel)
    {
        $this->kernel = $kernel;
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
        $queue = $this->queue;
        $this->queue = [];

        $exceptions = new ExceptionCollection();

        $httpCache = $this->kernel->getHttpCache();

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
