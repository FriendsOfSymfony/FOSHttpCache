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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

/**
 * Event raised by the HttpCache kernel.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class CacheEvent extends BaseEvent
{
    private CacheInvalidation $kernel;

    private Request $request;

    private ?Response $response;

    private int $requestType;

    /**
     * Make sure your $kernel implements CacheInvalidationInterface.
     *
     * @param CacheInvalidation $kernel      the kernel raising with this event
     * @param Request           $request     the request being processed
     * @param Response|null     $response    the response, if available
     * @param int               $requestType the request type (default HttpKernelInterface::MAIN_REQUEST)
     */
    public function __construct(CacheInvalidation $kernel, Request $request, Response $response = null, int $requestType = HttpKernelInterface::MAIN_REQUEST)
    {
        $this->kernel = $kernel;
        $this->request = $request;
        $this->response = $response;
        $this->requestType = $requestType;
    }

    /**
     * Get the cache kernel that raised this event.
     *
     * @return CacheInvalidation
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Get the request that is being processed.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * One of the constants HttpKernelInterface::MAIN_REQUEST or SUB_REQUEST.
     */
    public function getRequestType(): int
    {
        return $this->requestType;
    }

    /**
     * Events that occur after the response is created provide the default response.
     * Event listeners can also set the response to make it available here.
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Sets a response to use instead of continuing to handle this request.
     *
     * Setting a response stops propagation of the event to further event handlers.
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;

        $this->stopPropagation();
    }
}
