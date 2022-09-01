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
use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use FOS\HttpCache\ProxyClient\ProxyClient;
use FOS\HttpCache\ProxyClient\Symfony;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

/**
 * Manages HTTP cache invalidation.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 * @author André Rømcke <ar@ez.no>
 */
class CacheInvalidator
{
    /**
     * Value to check support of invalidatePath operation.
     */
    public const PATH = 'path';

    /**
     * Value to check support of refreshPath operation.
     */
    public const REFRESH = 'refresh';

    /**
     * Value to check support of invalidate and invalidateRegex operations.
     */
    public const INVALIDATE = 'invalidate';

    /**
     * Value to check support of invalidateTags operation.
     */
    public const TAGS = 'tags';

    /**
     * Value to check support of clearCache operation.
     */
    public const CLEAR = 'clear';

    /**
     * @var ProxyClient
     */
    private $cache;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Constructor.
     *
     * @param ProxyClient $cache HTTP cache
     */
    public function __construct(ProxyClient $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check whether this invalidator instance supports the specified
     * operation.
     *
     * Support for PATH means invalidatePath will work, REFRESH means
     * refreshPath works, TAGS means that invalidateTags works and
     * INVALIDATE is for the invalidate and invalidateRegex methods.
     *
     * @param string $operation one of the class constants
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    public function supports($operation)
    {
        switch ($operation) {
            case self::PATH:
                return $this->cache instanceof PurgeCapable;
            case self::REFRESH:
                return $this->cache instanceof RefreshCapable;
            case self::INVALIDATE:
                return $this->cache instanceof BanCapable;
            case self::TAGS:
                $supports = $this->cache instanceof TagCapable;
                if ($supports && $this->cache instanceof Symfony) {
                    return class_exists(Psr6Store::class);
                }

                return $supports;
            case self::CLEAR:
                return $this->cache instanceof ClearCapable;
            default:
                throw new InvalidArgumentException('Unknown operation '.$operation);
        }
    }

    /**
     * Set event dispatcher - may only be called once.
     *
     * @throws \Exception when trying to override the event dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        if ($this->eventDispatcher) {
            // if you want to set a custom event dispatcher, do so right after instantiating
            // the invalidator.
            throw new \Exception('You may not change the event dispatcher once it is set.');
        }
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get the event dispatcher used by the cache invalidator.
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
     * Invalidate a path or URL.
     *
     * @param string $path    Path or URL
     * @param array  $headers HTTP headers (optional)
     *
     * @return $this
     *
     * @throws UnsupportedProxyOperationException
     */
    public function invalidatePath($path, array $headers = [])
    {
        if (!$this->cache instanceof PurgeCapable) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('PURGE');
        }

        $this->cache->purge($path, $headers);

        return $this;
    }

    /**
     * Refresh a path or URL.
     *
     * @param string $path    Path or URL
     * @param array  $headers HTTP headers (optional)
     *
     * @see RefreshCapable::refresh()
     *
     * @return $this
     *
     * @throws UnsupportedProxyOperationException
     */
    public function refreshPath($path, array $headers = [])
    {
        if (!$this->cache instanceof RefreshCapable) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('REFRESH');
        }

        $this->cache->refresh($path, $headers);

        return $this;
    }

    /**
     * Invalidate all cached objects matching the provided HTTP headers.
     *
     * Each header is a a POSIX regular expression, for example
     * ['X-Host' => '^(www\.)?(this|that)\.com$']
     *
     * @see BanCapable::ban()
     *
     * @param array $headers HTTP headers that path must match to be banned
     *
     * @return $this
     *
     * @throws UnsupportedProxyOperationException If HTTP cache does not support BAN requests
     */
    public function invalidate(array $headers)
    {
        if (!$this->cache instanceof BanCapable) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('BAN');
        }

        $this->cache->ban($headers);

        return $this;
    }

    /**
     * Remove/Expire cache objects based on cache tags.
     *
     * @see TagCapable::tags()
     *
     * @param array $tags Tags that should be removed/expired from the cache
     *
     * @return $this
     *
     * @throws UnsupportedProxyOperationException If HTTP cache does not support Tags invalidation
     */
    public function invalidateTags(array $tags)
    {
        if (!$this->cache instanceof TagCapable) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('Tags');
        }
        $this->cache->invalidateTags($tags);

        return $this;
    }

    /**
     * Invalidate URLs based on a regular expression for the URI, an optional
     * content type and optional limit to certain hosts.
     *
     * The hosts parameter can either be a regular expression, e.g.
     * '^(www\.)?(this|that)\.com$' or an array of exact host names, e.g.
     * ['example.com', 'other.net']. If the parameter is empty, all hosts
     * are matched.
     *
     * @see BanCapable::banPath()
     *
     * @param string       $path        Regular expression pattern for URI to
     *                                  invalidate
     * @param string       $contentType Regular expression pattern for the content
     *                                  type to limit banning, for instance 'text'
     * @param array|string $hosts       Regular expression of a host name or list of
     *                                  exact host names to limit banning
     *
     * @return $this
     *
     * @throws UnsupportedProxyOperationException If HTTP cache does not support BAN requests
     */
    public function invalidateRegex($path, $contentType = null, $hosts = null)
    {
        if (!$this->cache instanceof BanCapable) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('BAN');
        }

        $this->cache->banPath($path, $contentType, $hosts);

        return $this;
    }

    /**
     * Clear the cache completely.
     *
     * @return $this
     *
     * @throws UnsupportedProxyOperationException if HTTP cache does not support clearing the cache completely
     */
    public function clearCache()
    {
        if (!$this->cache instanceof ClearCapable) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('CLEAR');
        }

        $this->cache->clear();

        return $this;
    }

    /**
     * Send all pending invalidation requests.
     *
     * @return int the number of cache invalidations performed per caching server
     *
     * @throws ExceptionCollection if any errors occurred during flush
     */
    public function flush()
    {
        try {
            return $this->cache->flush();
        } catch (ExceptionCollection $exceptions) {
            foreach ($exceptions as $exception) {
                $event = new Event();
                $event->setException($exception);
                if ($exception instanceof ProxyResponseException) {
                    $this->getEventDispatcher()->dispatch($event, Events::PROXY_RESPONSE_ERROR);
                } elseif ($exception instanceof ProxyUnreachableException) {
                    $this->getEventDispatcher()->dispatch($event, Events::PROXY_UNREACHABLE_ERROR);
                }
            }

            throw $exceptions;
        }
    }
}
