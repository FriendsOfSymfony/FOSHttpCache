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

use Http\Client\HttpAsyncClient;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\ResponseFactory;
use Http\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zend\Diactoros\ServerRequest;

/**
 * An implementation of HttpAsyncClient that allows direct routing through the
 * Symfony HttpCache kernel without executing a real HTTP request.
 * It uses the HttpFoundationFactory and the Zend DiactorosFactory to convert
 * between PSR-7 requests and responses. Both are optional dependencies of this
 * package thus existence of the respective classes is checked for in the
 * constructor of this client. It is only needed if you have a single node
 * setup of Symfony and serves as kind of a shortcut for easier configuration.
 * If you use Varnish or have a multiple node Symfony setup, this class is entirely
 * useless to you and you can happily ignore it.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class KernelClient implements HttpAsyncClient
{
    /**
     * @var HttpCacheAwareKernelInterface
     */
    private $kernel;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var ResponseFactory
     */
    private $psr7Factory;

    /**
     * KernelClient constructor.
     *
     * @param HttpCacheAwareKernelInterface $kernel
     */
    public function __construct(HttpCacheAwareKernelInterface $kernel, ResponseFactory $responseFactory = null)
    {
        $this->kernel = $kernel;

        if (!class_exists(HttpFoundationFactory::class)) {
            throw new \RuntimeException('Install symfony/psr-http-message-bridge to use this client.');
        }

        if (!class_exists(ResponseFactory::class)) {
            throw new \RuntimeException('Install a php-http/message-factory package to use this client.');
        }

        $this->httpFoundationFactory = new HttpFoundationFactory();
        $this->psr7Factory = $responseFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * Converts a PSR-7 request to a Symfony HttpFoundation request, runs
     * it through the HttpCache kernel and converts the response back to a PSR-7
     * response.
     *
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $symfonyRequest = Request::createFromGlobals();
        $symfonyRequest->server->set('REMOTE_ADDR', '127.0.0.1');

        $query = [];
        $parts = explode('&', $request->getUri()->getQuery());
        foreach ($parts as $part) {
            $chunks = explode('=', $part, 2);
            $query[$chunks[0]] = $chunks[1];
        }

        $serverRequest = new ServerRequest(
            $symfonyRequest->server->all(),
            [],
            $request->getUri(),
            $request->getMethod(),
            $request->getBody(),
            $request->getHeaders(),
            $symfonyRequest->cookies->all(),
            $query
        );

        $symfonyRequest = $this->httpFoundationFactory->createRequest($serverRequest);
        $symfonyResponse = $this->kernel->getHttpCache()->handle($symfonyRequest, HttpKernelInterface::MASTER_REQUEST, false);
        $psrResponse = $this->psr7Factory->createResponse($symfonyResponse);

        return new FulfilledPromise($psrResponse);
    }
}
