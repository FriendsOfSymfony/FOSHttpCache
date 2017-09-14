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
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Lock\StoreInterface as LockStoreInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Implements a storage for Symfony's HttpCache that supports tagging.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class TaggableStore implements StoreInterface
{
    const NON_VARYING_KEY = 'non-varying';
    const COUNTER_KEY = 'write-operations-counter';

    /**
     * @var array
     */
    private $options;

    /**
     * @var TagAwareAdapterInterface
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
     * When creating a TaggableStore you can configure a number options.
     *
     * - prune_threshold:       Configure the number of write actions until the
     *                          store will prune the expired cache entries. Pass
     *                          0 if you want to disable automated pruning.
     *                          Type: int
     *
     * - purge_tags_header:     The HTTP header name used to check for tags
     *                          Type: string
     *
     * - cache:                 The cache adapter.
     *                          Use this option if you want to use a different
     *                          cache implementation than the default one.
     *                          Note that there are very good reasons that the
     *                          local adapters are used by default. This is to
     *                          protect you as a developer!
     *                          Only override it if you're really sure your cache
     *                          implementation meets the needs of Symfony's HttpCache.
     *                          Type: TagAwareAdapterInterface
     *
     * - lock_factory:          The lock factory.
     *                          Use this option if you want to use a different
     *                          lock implementation than the default one.
     *                          Note that there are very good reasons that the
     *                          local adapters are used by default. This is to
     *                          protect you as a developer!
     *                          Only override it if you're really sure your lock
     *                          implementation meets the needs of Symfony's HttpCache.
     *                          Type: Factory
     *
     * @param string $cacheDir The cache directory
     * @param array  $options
     */
    public function __construct($cacheDir, array $options = [])
    {
        if (!class_exists(Factory::class)) {
            throw new \RuntimeException('You need at least Symfony 3.4 to use the TaggableStore.');
        }

        $resolver = new OptionsResolver();

        // Pruning threshold (set to 0 if you want to disable that feature)
        $resolver->setDefault('prune_threshold', 500)
            ->setAllowedTypes('prune_threshold', 'int');

        // HTTP header for tags
        $resolver->setDefault('purge_tags_header', PurgeTagsListener::DEFAULT_PURGE_TAGS_HEADER)
            ->setAllowedTypes('purge_tags_header', 'string');

        // Cache adapter
        $resolver->setDefault('cache', new TagAwareAdapter(
            new FilesystemAdapter('fos-http-cache', 0, $cacheDir))
        )->setAllowedTypes('cache', TagAwareAdapterInterface::class);

        // Lock factory
        $resolver->setDefault('lock_factory', new Factory(
            $this->getDefaultLockStore($cacheDir))
        )->setAllowedTypes('lock_factory', Factory::class);

        $this->options = $resolver->resolve($options);
        $this->cache = $this->options['cache'];
        $this->lockFactory = $this->options['lock_factory'];
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

            if (false === $this->saveDeferred($contentDigest, $response->getContent())) {
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

        // Tags
        $tags = [];
        if ($response->headers->has($this->options['purge_tags_header'])) {
            $tags = explode(',', $response->headers->get($this->options['purge_tags_header']));
        }

        // Prune expired entries on file system if needed
        $this->pruneExpiredEntries();

        $this->saveDeferred($cacheKey, $entries, $response->getMaxAge(), $tags);

        $this->cache->commit();

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
     * @param int    $expiresAfter
     * @param array  $tags
     *
     * @return bool
     */
    private function saveDeferred($key, $data, $expiresAfter = null, $tags = [])
    {
        $item = $this->cache->getItem($key);
        $item->set($data);
        $item->expiresAfter($expiresAfter);

        if (0 !== count($tags)) {
            $item->tag($tags);
        }

        return $this->cache->saveDeferred($item);
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

    /**
     * Build and return a default lock factory for when no explicit factory
     * was specified.
     * The default factory uses the best quality lock store that is available
     * on this system.
     *
     * @param string $cacheDir
     *
     * @return LockStoreInterface
     */
    private function getDefaultLockStore($cacheDir)
    {
        if (SemaphoreStore::isSupported(false)) {
            return new SemaphoreStore();
        } else {
            return new FlockStore($cacheDir);
        }
    }

    /**
     * Increases a counter every time an item is stored to the cache and then
     * prunes expired cache entries if a configurable threshold is reached.
     * This only happens during write operations so cache retrieval is not
     * slowed down.
     */
    private function pruneExpiredEntries()
    {
        if (!interface_exists(PruneableInterface::class)
            || !$this->cache instanceof PruneableInterface
            || 0 === $this->options['prune_threshold']
        ) {
            return;
        }

        $item = $this->cache->getItem(self::COUNTER_KEY);
        $counter = (int) $item->get();

        if ($counter > $this->options['prune_threshold']) {
            $this->cache->prune();
            $counter = 0;
        } else {
            ++$counter;
        }

        $item->set($counter);

        $this->cache->saveDeferred($item);
    }
}
