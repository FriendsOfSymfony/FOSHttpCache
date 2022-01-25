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

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Trait for enhanced Symfony reverse proxy based on the symfony kernel component.
 *
 * Your kernel needs to implement CacheInvalidatorInterface and redeclare the
 * fetch method as public. (The latter is needed because the trait declaring it
 * public does not satisfy the interface for whatever reason. See also
 * http://stackoverflow.com/questions/31877844/php-trait-exposing-a-method-and-interfaces )
 *
 * CacheInvalidator kernels support event listeners that can act on the
 * events defined in FOS\HttpCache\SymfonyCache\Events and may alter the
 * request flow.
 *
 * If your kernel overwrites any of the methods defined in this trait, make
 * sure to also call the trait method. You might get into issues with the order
 * of events, in which case you will need to copy event triggering into your
 * kernel.
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
trait EventDispatchingHttpCache
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Get event dispatcher.
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * Add an event subscriber.
     *
     * @see EventDispatcherInterface::addSubscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getEventDispatcher()->addSubscriber($subscriber);
    }

    /**
     * Add an event listener to this HttpCache.
     *
     * @see EventDispatcherInterface::addListener
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->getEventDispatcher()->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     *
     * Adding the Events::PRE_HANDLE and Events::POST_HANDLE events.
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true): Response
    {
        // trigger loading the CacheEvent to avoid fatal error when HttpKernel::loadClassCache is used.
        class_exists(CacheEvent::class);

        if ($response = $this->dispatch(Events::PRE_HANDLE, $request, null, $type)) {
            return $this->dispatch(Events::POST_HANDLE, $request, $response, $type);
        }

        $response = parent::handle($request, $type, $catch);

        return $this->dispatch(Events::POST_HANDLE, $request, $response, $type);
    }

    /**
     * {@inheritdoc}
     *
     * Trigger event to alter response before storing it in the cache.
     */
    protected function store(Request $request, Response $response)
    {
        $response = $this->dispatch(Events::PRE_STORE, $request, $response);

        parent::store($request, $response);
    }

    /**
     * {@inheritdoc}
     *
     * Adding the Events::PRE_INVALIDATE event.
     */
    protected function invalidate(Request $request, $catch = false): Response
    {
        if ($response = $this->dispatch(Events::PRE_INVALIDATE, $request)) {
            return $response;
        }

        return parent::invalidate($request, $catch);
    }

    /**
     * Dispatch an event if there are any listeners.
     *
     * @param string        $name        Name of the event to trigger. One of the constants in FOS\HttpCache\SymfonyCache\Events
     * @param Response|null $response    If already available
     * @param int           $requestType The request type (default HttpKernelInterface::MASTER_REQUEST)
     *
     * @return Response|null The response to return, which might be provided/altered by a listener
     */
    protected function dispatch($name, Request $request, Response $response = null, $requestType = HttpKernelInterface::MASTER_REQUEST): ?Response
    {
        if ($this->getEventDispatcher()->hasListeners($name)) {
            $event = new CacheEvent($this, $request, $response, $requestType);
            $this->getEventDispatcher()->dispatch($event, $name);

            $response = $event->getResponse();
        }

        return $response;
    }
}
