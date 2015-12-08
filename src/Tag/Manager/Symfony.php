<?php

namespace FOS\HttpCache\Tag\Manager;

use FOS\HttpCache\Tag\ManagerInterface;
use FOS\HttpCache\Tag\StorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tag manager for the Symfony HTTP Cache Proxy.
 */
class Symfony implements ManagerInterface
{
    /**
     * @var StorageInterface
     */
    private $tagStorage;

    /**
     * @var Store
     */
    private $cacheStorage;

    public function __construct(StorageInterface $tagStorage, Store $cacheStorage, Filesystem $filesystem = null)
    {
        $this->tagStorage = $tagStorage;
        $this->cacheStorage = $cacheStorage;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $digests = $this->tagStorage->getCacheIds($tags);

        foreach ($digests as $cacheDigest) {
            $cachePath = $this->cacheStorage->getPath($cacheDigest);

            $this->filesystem->remove($cachePath);
        }

        // remove the tag directory
        $this->tagStorage->removeTags($tags);
    }

    /**
     * {@inheritdoc}
     */
    public function tagCacheId(array $tags, $contentDigest, $lifetime)
    {
        $this->tagStorage->tagCacheId($tags, $contentDigest, $lifetime);
    }
}
