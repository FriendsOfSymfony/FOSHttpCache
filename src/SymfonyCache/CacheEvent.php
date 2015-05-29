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

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;

/**
 * Event raised by the HttpCache kernel.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class CacheEvent extends Event
{
    /**
     * @var CacheInvalidationInterface
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
     * Make sure your $kernel implements CacheInvalidationInterface. Creating the event with other
     * HttpCache classes is deprecated and will no longer be supported in version 2 of this library.
     *
     * @param CacheInvalidationInterface|HttpCache $kernel  The kernel raising with this event.
     * @param Request                              $request The request being processed.
     */
    public function __construct($kernel, Request $request)
    {
        if (!($kernel instanceof CacheInvalidationInterface || $kernel instanceof HttpCache)) {
            throw new \InvalidArgumentException('Expected a CacheInvalidationInterface or HttpCache');
        }
        $this->kernel = $kernel;
        $this->request = $request;
    }

    /**
     * Get the cache kernel that raised this event.
     *
     * @return CacheInvalidationInterface
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
     * @return Response|null The response if one was set.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets a response to use instead of continuing to handle this request.
     *
     * Setting a response stops propagation of the event to further event handlers.
     *
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        $this->stopPropagation();
    }
}
