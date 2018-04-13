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
use Http\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zend\Diactoros\ServerRequest;

class KernelClient implements HttpAsyncClient
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var HttpCacheAwareKernelInterface
     */
    private $kernel;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var DiactorosFactory
     */
    private $psr7Factory;

    /**
     * KernelClient constructor.
     *
     * @param RequestStack                       $requestStack
     * @param HttpCacheAwareKernelInterface|null $kernel
     */
    public function __construct(RequestStack $requestStack, HttpCacheAwareKernelInterface $kernel = null)
    {
        $this->requestStack = $requestStack;
        $this->kernel = $kernel;

        if (!class_exists(HttpFoundationFactory::class)) {
            throw new \RuntimeException('Install symfony/psr-http-message-bridge to use this client.');
        }

        if (!class_exists(DiactorosFactory::class)) {
            throw new \RuntimeException('Install zendframework/zend-diactoros to use this client.');
        }

        $this->httpFoundationFactory = new HttpFoundationFactory();
        $this->psr7Factory = new DiactorosFactory();
        $this->requestStack = $requestStack;
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
        $currentRequest = $this->requestStack->getCurrentRequest();

        $serverRequest = new ServerRequest(
            $currentRequest->server->all(),
            [],
            $request->getUri(),
            $request->getMethod(),
            $request->getBody(),
            $request->getHeaders(),
            [],
            explode('&', $request->getUri()->getQuery())
        );

        $symfonyRequest = $this->httpFoundationFactory->createRequest($serverRequest);
        $symfonyResponse = $this->kernel->getHttpCache()->handle($symfonyRequest, HttpKernelInterface::MASTER_REQUEST, false);
        $psrResponse = $this->psr7Factory->createResponse($symfonyResponse);

        return new FulfilledPromise($psrResponse);
    }
}
