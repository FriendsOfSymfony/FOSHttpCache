<?php

namespace FOS\HttpCache\SymfonyCache;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\HttpCache\Tag\ManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use FOS\HttpCache\ProxyClient\Symfony;
use FOS\HttpCache\SymfonyCache\Events;

/**
 * This class associates responses from the Symfony HTTP proxy cache with tags.
 *
 * Firstly, in order that tags may be associated with a response, the
 * application must add a header to the response (@see
 * Symfony::HTTP_HEADER_TAGS) containing the tag names. The list of tag names
 * MUST be JSON encoded.
 *
 * The `postHandle` method is called *after* the Symfony HttpProxy has stored
 * the response in the cache and has set the "content digest" in the response.
 *
 * The `postHandle` method will associate the content digest with the tags found in
 * the header.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 * 
 * {@inheritdoc}
 */
class TagSubscriber implements EventSubscriberInterface
{
    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @param ManagerInterface $manager
     * @param mixed $options
     */
    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::POST_HANDLE => 'postHandle',
        ];
    }

    /**
     * Check to see if the response contains tags which should be associated
     * with the cached page.
     *
     * @param CacheEvent
     */
    public function postHandle(CacheEvent $event)
    {
        $response = $event->getResponse();

        if ($response->headers->has(Symfony::HTTP_HEADER_TAGS)) {
            $this->storeTagsFromResponse($response);
        }

        if ($response->headers->has(Symfony::HTTP_HEADER_INVALIDATE_TAGS)) {
            $this->invalidateTagsFromResponse($response);
        }
    }

    /**
     * Store tags and associate them with the response.
     *
     * @param Response
     */
    private function storeTagsFromResponse(Response $response)
    {
        $contentDigest = $this->getContentDigestFromHeaders($response->headers);
        $tags = $this->getTagsFromHeaders($response->headers);
        $lifetime = $this->getExpiryFromResponse($response);
        $this->manager->tagCacheId($tags, $contentDigest, $lifetime);
    }

    public function invalidateTagsFromResponse(Response $response)
    {
        $tags = json_decode($response->headers->get(Symfony::HTTP_HEADER_INVALIDATE_TAGS));

        if (null === $tags) {
            // could not decode the tags
            return;
        }

        $this->manager->invalidateTags($tags);
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
        if (!$headers->has(Symfony::HTTP_HEADER_CONTENT_DIGEST)) {
            throw new \RuntimeException(sprintf(
                'Could not find content digest header: "%s". Got headers: "%s"',
                Symfony::HTTP_HEADER_CONTENT_DIGEST,
                implode('", "', array_keys($headers->all()))
            ));
        }

        return $headers->get(Symfony::HTTP_HEADER_CONTENT_DIGEST);
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
        if (!$headers->has(Symfony::HTTP_HEADER_TAGS)) {
            throw new \RuntimeException(sprintf(
                'Could not find tags header "%s"',
                Symfony::HEADER_TAGS
            ));
        }

        $tagsRaw = $headers->get(Symfony::HTTP_HEADER_TAGS);
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
     * Determine the cache lifetime time from the response headers.
     *
     * If no lifetime can be inferred, then return NULL.
     *
     * @return integer|null
     */
    private function getExpiryFromResponse(Response $response)
    {
        return $response->getMaxAge();
    }
}
