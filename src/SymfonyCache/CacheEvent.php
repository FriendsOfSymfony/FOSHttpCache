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
    /**
     * @var CacheInvalidation
     */
    private $kernel;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var int
     */
    private $requestType;

    /**
     * Make sure your $kernel implements CacheInvalidationInterface.
     *
     * @param CacheInvalidation $kernel      the kernel raising with this event
     * @param Request           $request     the request being processed
     * @param Response|null     $response    the response, if available
     * @param int               $requestType the request type (default HttpKernelInterface::MASTER_REQUEST)
     */
    public function __construct(CacheInvalidation $kernel, Request $request, Response $response = null, $requestType = HttpKernelInterface::MASTER_REQUEST)
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
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * One of the constants HttpKernelInterface::MASTER_REQUEST or SUB_REQUEST.
     *
     * @return int
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Events that occur after the response is created provide the default response.
     * Event listeners can also set the response to make it available here.
     *
     * @return Response|null the response if one was set
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets a response to use instead of continuing to handle this request.
     *
     * Setting a response stops propagation of the event to further event handlers.
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        $this->stopPropagation();
    }
}
