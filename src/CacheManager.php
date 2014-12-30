<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\Exception\UnsupportedProxyOperationException;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages HTTP cache invalidation.
 *
 * @author David de Boer <david@driebit.nl>
 * @author Daniel Leech <daniel@dantleech.com>
 */
class CacheManager
{
    /**
     * @var array
     */
    private $handlers;

    /**
     * @var array
     */
    private $handlerFlushRegistry;

    public function __construct($handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * Invoke the invalidate on the named cache handler
     *
     * @see CacheHandlerInterface::invaidate
     *
     * @param string $handlerName
     * @param mixed $subject
     * @param array $options
     */
    public function invalidate($handlerName, $subject, array $options = array())
    {
        $handler = $this->getHandler($handlerName);
        $handler->invalidate($handlerName, $subject, $options);

        $this->handlerFlushRegistry[$handlerName] = $handler->getCacheClass();
    }

    /**
     * Invoke the refresh on the named cache handler
     *
     * @see CacheHandlerInterface::refresh
     *
     * @param string $handlerName
     * @param mixed $subject
     * @param array $options
     */
    public function refresh($handlerName, $subject, array $options = array())
    {
        $this->getHandler($handlerName)->refresh($handlerName, $subject, $options);
    }

    /**
     * Invoke updateResponse on the named cache handler
     *
     * @see CacheHandlerInterface::updateResponse
     *
     * @param string $handlerName
     * @param mixed $subject
     * @param Response $response
     */
    public function updateResponse($handlerName, $subject, Response $response)
    {
        $this->getHandler($handlerName)->updateResponse($handlerName, $subject, $response);
    }

    /**
     * Issue a flush request to all the handlers in the stack
     *
     * @return int The number of cache invalidations performed per caching server.
     *
     * @throws ExceptionCollection If any errors occurred during flush.
     */
    public function flush()
    {
        $nbOperations = 0;
        $exceptionCollection = new ExceptionCollection();
        $flushedCacheClasses = array();

        foreach (array_keys($this->handlerFlushRegistry) as $handlerName => $cacheClass) {
            if (isset($flushedCacheClasses[$cacheClass])) {
                continue;
            }

            $handler = $this->getHandler($handlerName)->flush();

            try {
                $nbOperations += $handler->flush();
            } catch (ExceptionCollection $exceptions) {
                $exceptionCollection->merge($exceptions);
            }

            $flushedCacheClasses[$cacheClass] = true;
        }

        $this->handlerFlushRegistry = array();

        if (count($exceptionCollection)) {
            throw $exceptionCollection;
        }

        return $nbOperations;
    }

    /**
     * Return the named handler
     *
     * @param string $handlerName
     * @return CacheHandlerInterface
     */
    private function getHandler($handlerName)
    {
        if (!isset($this->handlers[$handlerName])) {
            throw HandlerNotFoundException::handlerNotFound($handlerName, array_keys($this->handlers));
        }

        return $this->handlers[$handlerName];
    }
}
