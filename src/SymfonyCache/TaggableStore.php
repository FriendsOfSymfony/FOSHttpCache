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

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

/**
 * Implements a storage for Symfony's HttpCache that supports tagging.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class TaggableStore implements StoreInterface
{
    const NON_VARYING_KEY = 'non-varying';

    /**
     * @var string
     */
    private $purgeTagsHeader;

    /**
     * @var TagAwareAdapter
     */
    private $cache;

    /**
     * @var Factory
     */
    private $lockFactory;

    /**
     * @var LockInterface[]
     */
    private $locks = [];

    /**
     * @param string $cacheDir
     */
    public function __construct($cacheDir, $purgeTagsHeader = PurgeTagsListener::DEFAULT_PURGE_TAGS_HEADER)
    {
        if (!class_exists(Factory::class)) {
            throw new \RuntimeException('You need at least Symfony 3.4 to use the TaggableStore.');
        }

        $this->purgeTagsHeader = $purgeTagsHeader;
        $this->cache = new TagAwareAdapter(new FilesystemAdapter('fos-http-cache', 0, $cacheDir));

        if (SemaphoreStore::isSupported(false)) {
            $store = new SemaphoreStore();
        } else {
            $store = new FlockStore($cacheDir);
        }

        $this->lockFactory = new Factory($store);
    }

    /**
     * Locates a cached Response for the Request provided.
     *
     * @param Request $request A Request instance
     *
     * @return Response|null A Response instance, or null if no cache entry was found
     */
    public function lookup(Request $request)
    {
        $cacheKey = $this->getCacheKey($request);

        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            return null;
        }

        $entries = $item->get();

        foreach ($entries as $varyKeyResponse => $responseData) {
            // This can only happen if one entry only
            if (self::NON_VARYING_KEY === $varyKeyResponse) {
                return $this->restoreResponse($responseData);
            }

            // Otherwise we have to see if Vary headers match
            $varyKeyRequest = $this->getVaryKey(
                $responseData['vary'],
                $request->headers
            );

            if ($varyKeyRequest === $varyKeyResponse) {
                return $this->restoreResponse($responseData);
            }
        }

        return null;
    }

    /**
     * Writes a cache entry to the store for the given Request and Response.
     *
     * Existing entries are read and any that match the response are removed. This
     * method calls write with the new list of cache entries.
     *
     * @param Request  $request  A Request instance
     * @param Response $response A Response instance
     *
     * @return string The key under which the response is stored
     */
    public function write(Request $request, Response $response)
    {
        if (!$response->headers->has('X-Content-Digest')) {
            $contentDigest = $this->generateContentDigest($response);

            if (false === $this->save($contentDigest, $response->getContent())) {
                throw new \RuntimeException('Unable to store the entity.');
            }

            $response->headers->set('X-Content-Digest', $contentDigest);

            if (!$response->headers->has('Transfer-Encoding')) {
                $response->headers->set('Content-Length', strlen($response->getContent()));
            }
        }

        $cacheKey = $this->getCacheKey($request);
        $headers = $response->headers->all();
        unset($headers['age']);

        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            $entries = [];
        } else {
            $entries = $item->get();
        }

        // Add or replace entry with current Vary header key
        $entries[$this->getVaryKey($response->getVary(), $response->headers)] = [
            'vary' => $response->getVary(),
            'headers' => $headers,
            'status' => $response->getStatusCode(),
        ];

        // If the response has a Vary header we remove the non-varying entry
        if ($response->hasVary()) {
            unset($entries[self::NON_VARYING_KEY]);
        }

        // Support tagging
        $tags = [];
        if ($response->headers->has($this->purgeTagsHeader)) {
            $tags = explode(',', $response->headers->get($this->purgeTagsHeader));
        }

        $this->save($cacheKey, $entries, $tags);

        return $cacheKey;
    }

    /**
     * Invalidates all cache entries that match the request.
     *
     * @param Request $request A Request instance
     */
    public function invalidate(Request $request)
    {
        $cacheKey = $this->getCacheKey($request);

        $this->cache->deleteItem($cacheKey);
    }

    /**
     * Locks the cache for a given Request.
     *
     * @param Request $request A Request instance
     *
     * @return bool|string true if the lock is acquired, the path to the current lock otherwise
     */
    public function lock(Request $request)
    {
        $cacheKey = $this->getCacheKey($request);

        if (isset($this->locks[$cacheKey])) {
            return false;
        }

        $this->locks[$cacheKey] = $this->lockFactory
            ->createLock($cacheKey);

        return $this->locks[$cacheKey]->acquire();
    }

    /**
     * Releases the lock for the given Request.
     *
     * @param Request $request A Request instance
     *
     * @return bool False if the lock file does not exist or cannot be unlocked, true otherwise
     */
    public function unlock(Request $request)
    {
        $cacheKey = $this->getCacheKey($request);

        if (!isset($this->locks[$cacheKey])) {
            return false;
        }

        try {
            $this->locks[$cacheKey]->release();
        } catch (LockReleasingException $e) {
            return false;
        } finally {
            unset($this->locks[$cacheKey]);
        }

        return true;
    }

    /**
     * Returns whether or not a lock exists.
     *
     * @param Request $request A Request instance
     *
     * @return bool true if lock exists, false otherwise
     */
    public function isLocked(Request $request)
    {
        $cacheKey = $this->getCacheKey($request);

        if (!isset($this->locks[$cacheKey])) {
            return false;
        }

        return $this->locks[$cacheKey]->isAcquired();
    }

    /**
     * Purges data for the given URL.
     *
     * @param string $url A URL
     *
     * @return bool true if the URL exists and has been purged, false otherwise
     */
    public function purge($url)
    {
        $cacheKey = $this->getCacheKey(Request::create($url));

        return $this->cache->deleteItem($cacheKey);
    }

    /**
     * Release all locks.
     *
     * {@inheritdoc}
     */
    public function cleanup()
    {
        try {
            /** @var LockInterface $lock */
            foreach ($this->locks as $lock) {
                $lock->release();
            }
        } catch (LockReleasingException $e) {
            // noop
        } finally {
            $this->locks = [];
        }
    }

    /**
     * Remove/Expire cache objects based on cache tags.
     * Returns true on success and false otherwise.
     *
     * @param array $tags Tags that should be removed/expired from the cache
     *
     * @return bool
     */
    public function invalidateTags(array $tags)
    {
        try {
            return $this->cache->invalidateTags($tags);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getCacheKey(Request $request)
    {
        // Strip scheme to treat https and http the same
        $uri = $request->getUri();
        $uri = substr($uri, strlen($request->getScheme().'://'));

        return 'md'.hash('sha256', $uri);
    }

    /**
     * @param array     $vary
     * @param HeaderBag $headerBag
     *
     * @return string
     */
    public function getVaryKey(array $vary, HeaderBag $headerBag)
    {
        if (0 === count($vary)) {
            return self::NON_VARYING_KEY;
        }

        sort($vary);

        $hashData = '';

        foreach ($vary as $headerName) {
            $hashData .= $headerName.':'.$headerBag->get($headerName);
        }

        return hash('sha256', $hashData);
    }

    /**
     * @param Response $response
     *
     * @return string
     */
    public function generateContentDigest(Response $response)
    {
        return 'en'.hash('sha256', $response->getContent());
    }

    /**
     * @param string $key
     * @param string $data
     * @param array  $tags
     *
     * @return bool
     */
    private function save($key, $data, $tags = [])
    {
        $item = $this->cache->getItem($key);
        $item->set($data);

        if (0 !== count($tags)) {
            $item->tag($tags);
        }

        return $this->cache->save($item);
    }

    /**
     * Restores a Response from the cached data.
     *
     * @param array $cacheData An array containing the cache data
     *
     * @return Response|null
     */
    private function restoreResponse(array $cacheData)
    {
        $body = null;

        if (isset($cacheData['headers']['x-content-digest'][0])) {
            $item = $this->cache->getItem($cacheData['headers']['x-content-digest'][0]);
            if ($item->isHit()) {
                $body = $item->get();
            }
        }

        return new Response(
            $body,
            $cacheData['status'],
            $cacheData['headers']
        );
    }
}
