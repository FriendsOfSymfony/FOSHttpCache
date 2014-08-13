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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manages HTTP cache invalidation.
 *
 * @author David de Boer <david@driebit.nl>
 */
class CacheInvalidator
{
    /**
     * Value to check support of invalidatePath operation.
     */
    const PATH = 'path';

    /**
     * Value to check support of refreshPath operation.
     */
    const REFRESH = 'refresh';

    /**
     * Value to check support of invalidate operation.
     */
    const INVALIDATE = 'invalidate';

    /**
     * @var ProxyClientInterface
     */
    private $cache;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $tagsHeader = 'X-Cache-Tags';

    /**
     * Constructor
     *
     * @param ProxyClientInterface $cache HTTP cache
     */
    public function __construct(ProxyClientInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check whether this invalidator instance supports the specified
     * operation.
     *
     * Support for PATH means invalidatePath will work, REFRESH means
     * refreshPath works and INVALIDATE is about all other invalidation
     * methods.
     *
     * @param string $operation one of the class constants.
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    public function supports($operation)
    {
        switch ($operation) {
            case self::PATH:
                return $this->cache instanceof PurgeInterface;
            case self::REFRESH:
                return $this->cache instanceof RefreshInterface;
            case self::INVALIDATE:
                return $this->cache instanceof BanInterface;
            default:
                throw new InvalidArgumentException('Unknown operation ' . $operation);
        }
    }

    /**
     * Set event dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get event dispatcher
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
     * Add subscriber
     *
     * @param EventSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getEventDispatcher()->addSubscriber($subscriber);

        return $this;
    }

    /**
     * Set the HTTP header name that will hold cache tags
     *
     * @param string $tagsHeader
     *
     * @return $this
     */
    public function setTagsHeader($tagsHeader)
    {
        $this->tagsHeader = $tagsHeader;

        return $this;
    }

    /**
     * Get the HTTP header name that will hold cache tags
     *
     * @return string
     */
    public function getTagsHeader()
    {
        return $this->tagsHeader;
    }

    /**
     * Invalidate a path or URL
     *
     * @param string $path    Path or URL
     * @param array  $headers HTTP headers (optional)
     *
     * @throws UnsupportedProxyOperationException
     *
     * @return $this
     */
    public function invalidatePath($path, array $headers = array())
    {
        if (!$this->cache instanceof PurgeInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('PURGE');
        }

        $this->cache->purge($path, $headers);

        return $this;
    }

    /**
     * Refresh a path or URL
     *
     * @param string $path    Path or URL
     * @param array  $headers HTTP headers (optional)
     *
     * @see RefreshInterface::refresh()
     *
     * @throws UnsupportedProxyOperationException
     *
     * @return $this
     */
    public function refreshPath($path, array $headers = array())
    {
        if (!$this->cache instanceof RefreshInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('REFRESH');
        }

        $this->cache->refresh($path, $headers);

        return $this;
    }

    /**
     * Invalidate all cached objects matching the provided HTTP headers.
     *
     * Each header is a a POSIX regular expression, for example
     * array('X-Host' => '^(www\.)?(this|that)\.com$')
     *
     * @see BanInterface::ban()
     *
     * @param array $headers HTTP headers that path must match to be banned.
     *
     * @throws UnsupportedProxyOperationException If HTTP cache does not support BAN requests
     *
     * @return $this
     */
    public function invalidate(array $headers)
    {
        if (!$this->cache instanceof BanInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('BAN');
        }

        $this->cache->ban($headers);

        return $this;
    }

    /**
     * Invalidate URLs based on a regular expression for the URI, an optional
     * content type and optional limit to certain hosts.
     *
     * The hosts parameter can either be a regular expression, e.g.
     * '^(www\.)?(this|that)\.com$' or an array of exact host names, e.g.
     * array('example.com', 'other.net'). If the parameter is empty, all hosts
     * are matched.
     *
     * @see BanInterface::banPath()
     *
     * @param string       $path        Regular expression pattern for URI to
     *                                  invalidate.
     * @param string       $contentType Regular expression pattern for the content
     *                                  type to limit banning, for instance 'text'.
     * @param array|string $hosts       Regular expression of a host name or list of
     *                                  exact host names to limit banning.
     *
     * @throws UnsupportedProxyOperationException If HTTP cache does not support BAN requests
     *
     * @return $this
     */
    public function invalidateRegex($path, $contentType = null, $hosts = null)
    {
        if (!$this->cache instanceof BanInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('BAN');
        }

        $this->cache->banPath($path, $contentType, $hosts);

        return $this;
    }

    /**
     * Invalidate cache entries that contain any of the specified tags in their
     * tag header.
     *
     * @see BanInterface::ban()
     *
     * @param array $tags Cache tags
     *
     * @throws UnsupportedProxyOperationException If HTTP cache does not support BAN requests
     *
     * @return $this
     */
    public function invalidateTags(array $tags)
    {
        if (!$this->cache instanceof BanInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('BAN');
        }

        $headers = array($this->getTagsHeader() => '('.implode('|', array_map('preg_quote', $tags)).')(,.+)?$');
        $this->cache->ban($headers);

        return $this;
    }

    /**
     * Send all pending invalidation requests.
     *
     * @return int The number of cache invalidations performed per caching server.
     *
     * @throws ExceptionCollection If any errors occurred during flush.
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
                    $this->getEventDispatcher()->dispatch(Events::PROXY_RESPONSE_ERROR, $event);
                } elseif ($exception instanceof ProxyUnreachableException) {
                    $this->getEventDispatcher()->dispatch(Events::PROXY_UNREACHABLE_ERROR, $event);
                }
            }

            throw $exceptions;
        }
    }
}
