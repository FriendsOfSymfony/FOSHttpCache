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

        if (false === $response->headers->has(Symfony::HTTP_HEADER_TAGS)) {
            return;
        }

        $this->tagResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    private function tagResponse(Response $response)
    {
        $contentDigest = $this->getContentDigestFromHeaders($response->headers);
        $tags = $this->getTagsFromHeaders($response->headers);
        $expiry = $this->getExpiryFromResponse($response);
        $this->manager->tagCacheId($tags, $contentDigest, $expiry);
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
