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
     * Name for HTTP header containing the tags (for both invalidation and
     * initial tagging).
     */
    const HEADER_TAGS = 'X-TaggedCache-Tags';

    /**
     * Header which should contain the content digest produced by the Symfony
     * HTTP cache.
     */
    const HEADER_CONTENT_DIGEST = 'X-Content-Digest';

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
    public function tagResponse(Response $response)
    {
        $contentDigest = $this->getContentDigestFromHeaders($response->headers);
        $tags = $this->getTagsFromHeaders($response->headers);
        $expiry = $this->getExpiryFromResponse($response);
        $this->tagStorage->tagCacheId($tags, $contentDigest, $expiry);
    }

    /**
     * Return the content digest from the headers.
     * The content digest should be set by the Symfony HTTP cache before
     * this method is invoked.
     *
     * If the content digest cannot be found then a \RuntimeException
     * is thrown.
     *
     * @param HeaderBag $headers
     * @return string
     * @throws RuntimeException
     */
    private function getContentDigestFromHeaders(HeaderBag $headers)
    {
        if (!$headers->has(self::HEADER_CONTENT_DIGEST)) {
            throw new \RuntimeException(sprintf(
                'Could not find content digest header: "%s". Got headers: "%s"',
                self::HEADER_CONTENT_DIGEST,
                implode('", "', array_keys($headers->all()))
            ));
        }

        return $headers->get(self::HEADER_CONTENT_DIGEST);
    }

    /**
     * Retrieve and decode the tag list from the response
     * headers.
     *
     * If no tags header is found then throw a \RuntimeException.
     * If the JSON is invalid then throw a \RuntimeException
     *
     * @param HeaderBag $headers
     * @return string[]
     * @throws \RuntimeException
     */
    private function getTagsFromHeaders(HeaderBag $headers)
    {
        if (!$headers->has(self::HEADER_TAGS)) {
            throw new \RuntimeException(sprintf(
                'Could not find tags header "%s"',
                self::HEADER_TAGS
            ));
        }

        $tagsRaw = $headers->get(self::HEADER_TAGS);
        $tags = json_decode($tagsRaw, true);

        if (null === $tags) {
            throw new \RuntimeException(sprintf(
                'Could not JSON decode tags header with value "%s"',
                $tagsRaw
            ));
        }

        return $tags;
    }

    /**
     * Determine the cache expiry time from the response headers.
     *
     * If no expiry can be inferred, then return NULL.
     *
     * @return integer|null
     */
    private function getExpiryFromResponse(Response $response)
    {
        return $response->getMaxAge();
    }
}
