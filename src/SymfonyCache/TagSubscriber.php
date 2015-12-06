<?php

namespace FOS\HttpCache\SymfonyCache;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\HttpCache\SymfonyCache\Tag\ManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use FOS\HttpCache\ProxyClient\Symfony;

/**
 * Adds tag invalidation capabilities to the Symfony HTTP cache
 *
 * @author Daniel Leech <daniel@dantleech.com>
 * 
 * {@inheritdoc}
 */
class TagSubscriber implements EventSubscriberInterface
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
     * @var ManagerInterface
     */
    private $tagManager;

    /**
     * @param ManagerInterface $tagManager
     * @param mixed $options
     */
    public function __construct(ManagerInterface $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
            Events::POST_HANDLE => 'postHandle',
        ];
    }

    /**
     * Check to see if the request is an invalidation request, if so
     * handle the invalidation.
     *
     * @param CacheEvent $event
     */
    public function preHandle(CacheEvent $event)
    {
        $request = $event->getRequest();

        if (Symfony::HTTP_METHOD_INVALIDATE!== $request->getMethod()) {
            return;
        }

        $event->setResponse(
            $this->handleInvalidate($request)
        );
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

        if (false === $response->headers->has(self::HEADER_TAGS)) {
            return;
        }

        $this->handleTags($response);
    }

    private function handleInvalidate(Request $request)
    {
        $tags = $this->getTagsFromHeaders($request->headers);
        $nbCacheEntries = $this->tagManager->invalidateTags($tags);

        return new JsonResponse(array(
            'Status' => 'PURGED',
            'NbCacheEntries' => $nbCacheEntries
        ));
    }

    private function handleTags(Response $response)
    {
        if (!$response->headers->has(self::HEADER_CONTENT_DIGEST)) {
            throw new \RuntimeException(sprintf(
                'Could not find content digest in the header: "%s". Got headers: "%s"',
                self::HEADER_CONTENT_DIGEST,
                implode('", "', array_keys($response->headers->all()))
            ));
        }

        $contentDigest = $response->headers->get(self::HEADER_CONTENT_DIGEST);
        $tags = $this->getTagsFromHeaders($response->headers);

        foreach ($tags as $tag) {
            $this->tagManager->tagCacheReference($tag, $ref);
        }
    }

    private function getTagsFromHeaders(HeaderBag $headers)
    {
        if (!$headers->has(self::HEADER_TAGS)) {
            throw new \RuntimeException(sprintf(
                'Could not find header "%s"',
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
}
