<?php

namespace FOS\HttpCache;

use FOS\HttpCache\Exception\UnsupportedInvalidationMethodException;
use FOS\HttpCache\Invalidation\CacheProxyInterface;
use FOS\HttpCache\Invalidation\Method\BanInterface;
use FOS\HttpCache\Invalidation\Method\PurgeInterface;
use FOS\HttpCache\Invalidation\Method\RefreshInterface;

/**
 * Manages HTTP cache invalidation.
 *
 * @author David de Boer <david@driebit.nl>
 */
class CacheInvalidator
{
    /**
     * @var string
     */
    protected $tagsHeader = 'X-Cache-Tags';

    /**
     * @var CacheProxyInterface
     */
    protected $cache;

    /**
     * Constructor
     *
     * @param CacheProxyInterface $cache  HTTP cache
     */
    public function __construct(CacheProxyInterface $cache)
    {
        $this->cache = $cache;
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
     * @param string $path Path or URL
     *
     * @return $this
     *
     * @throws UnsupportedInvalidationMethodException
     */
    public function invalidatePath($path)
    {
        if (!$this->cache instanceof PurgeInterface) {
            throw UnsupportedInvalidationMethodException::cacheDoesNotImplement('PURGE');
        }

        $this->cache->purge($path);

        return $this;
    }

    /**
     * Refresh a path or URL
     *
     * @param string $path   Path or URL
     * @param array $headers HTTP headers (optional)
     *
     * @return $this
     *
     * @throws UnsupportedInvalidationMethodException
     */
    public function refreshPath($path, array $headers = array())
    {
        if (!$this->cache instanceof RefreshInterface) {
            throw UnsupportedInvalidationMethodException::cacheDoesNotImplement('REFRESH');
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
     * @param array $headers HTTP headers that path must match to be banned.
     *
     * @return $this
     *
     * @throws UnsupportedInvalidationMethodException If HTTP cache does not support BAN requests
     *
     * @see BanInterface::ban
     */
    public function invalidate(array $headers)
    {
        if (!$this->cache instanceof BanInterface) {
            throw UnsupportedInvalidationMethodException::cacheDoesNotImplement('BAN');
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

     * @param string $path        Regular expression pattern for URI to
     *                            invalidate.
     * @param string $contentType Regular expression pattern for the content
     *                            type to limit banning, for instance 'text'.
     * @param array|string $hosts Regular expression of a host name or list of
     *                            exact host names to limit banning.
     *
     * @return $this
     *
     * @throws UnsupportedInvalidationMethodException If HTTP cache does not support BAN requests
     *
     * @see BanInterface::banPath
     */
    public function invalidateRegex($path, $contentType = null, $hosts = null)
    {
        if (!$this->cache instanceof BanInterface) {
            throw UnsupportedInvalidationMethodException::cacheDoesNotImplement('BAN');
        }

        $this->cache->banPath($path, $contentType, $hosts);

        return $this;
    }

    /**
     * Invalidate cache entries that contain any of the specified tags in their
     * tag header.
     *
     * @param array $tags Cache tags
     *
     * @return $this
     *
     * @throws UnsupportedInvalidationMethodException If HTTP cache does not support BAN requests
     */
    public function invalidateTags(array $tags)
    {
        if (!$this->cache instanceof BanInterface) {
            throw UnsupportedInvalidationMethodException::cacheDoesNotImplement('BAN');
        }

        $headers = array($this->getTagsHeader() => '('.implode('|', $tags).')(,.+)?$');
        $this->cache->ban($headers);

        return $this;
    }

    /**
     * Send all pending invalidation requests.
     *
     * @return $this
     */
    public function flush()
    {
        $this->cache->flush();

        return $this;
    }
}
