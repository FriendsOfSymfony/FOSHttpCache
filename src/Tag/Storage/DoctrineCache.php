<?php

namespace FOS\HttpCache\Tag\Storage;

use Doctrine\Common\Cache\Cache;
use FOS\HttpCache\Tag\StorageInterface;

/**
 * Tag storage implementation which uses the Doctrine Cache library.
 */
class DoctrineCache implements StorageInterface
{
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function tagCacheId(array $tags, $identifier, $expiry = null)
    {
        foreach ($tags as $tag) {
            $identifiers = $this->getCacheIds([$tag]);
            $identifiers[] = $identifier;
            $encodedIdentifiers = json_encode($identifiers, true);
            $this->cache->save($tag, $encodedIdentifiers, $expiry);
        }
    }


    /**
     * {@inheritDoc}
     */
    public function removeTags(array $tags)
    {
        foreach ($tags as $tag) {
            // doctrine does not care if the key does not exist.
            $this->cache->delete($tag);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheIds(array $tags)
    {
        $ret = array();

        foreach ($tags as $tag) {
            $encodedIdentifiers = $this->cache->fetch($tag);

            if (!$encodedIdentifiers) {
                continue;
            }

            $identifiers = json_decode($encodedIdentifiers);

            // this should never happen, so fail loudly.
            if (null === $identifiers) {
                throw new \RuntimeException(sprintf(
                    'Could not decode cache entry, invalid JSON: %s',
                    $encodedIdentifiers
                ));
            }

            $ret = array_merge($ret, $identifiers);
        }

        return $ret;
    }
}
