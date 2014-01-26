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
class CacheManager
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

    public function invalidateRegex($regex)
    {
        throw new \RuntimeException('not implemented yet');
    }

    /**
     * Invalidate cache tags
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
