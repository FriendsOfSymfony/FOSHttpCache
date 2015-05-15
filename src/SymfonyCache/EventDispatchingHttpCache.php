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

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Base class for enhanced Symfony reverse proxy based on the symfony component.
 *
 * <b>When using FOSHttpCacheBundle, look at FOS\HttpCacheBundle\HttpCache instead.</b>
 *
 * This kernel supports event subscribers that can act on the events defined in
 * FOS\HttpCache\SymfonyCache\Events and may alter the request flow.
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 *
 * {@inheritdoc}
 */
abstract class EventDispatchingHttpCache extends HttpCache implements CacheInvalidationInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Get event dispatcher
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * Add subscriber
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getEventDispatcher()->addSubscriber($subscriber);
    }

    /**
     * {@inheritDoc}
     *
     * Adding the Events::PRE_HANDLE event.
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if ($this->getEventDispatcher()->hasListeners(Events::PRE_HANDLE)) {
            $event = new CacheEvent($this, $request);
            $this->getEventDispatcher()->dispatch(Events::PRE_HANDLE, $event);
            if ($event->getResponse()) {
                return $event->getResponse();
            }
        }

        return parent::handle($request, $type, $catch);
    }

    /**
     * Made public to allow event subscribers to do refresh operations.
     *
     * {@inheritDoc}
     */
    public function fetch(Request $request, $catch = false)
    {
        return parent::fetch($request, $catch);
    }

    /**
     * {@inheritDoc}
     *
     * Adding the Events::PRE_INVALIDATE event.
     */
    protected function invalidate(Request $request, $catch = false)
    {
        if ($this->getEventDispatcher()->hasListeners(Events::PRE_INVALIDATE)) {
            $event = new CacheEvent($this, $request);
            $this->getEventDispatcher()->dispatch(Events::PRE_INVALIDATE, $event);
            if ($event->getResponse()) {
                return $event->getResponse();
            }
        }

        return parent::invalidate($request, $catch);
    }
}
