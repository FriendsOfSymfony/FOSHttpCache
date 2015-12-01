<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

use Symfony\Component\HttpFoundation\Request;
use FOS\HttpCache\SymfonyCache\Tag\TagInvalidatorInterface;
use FOS\HttpCache\ProxyClient\Request\InvalidationRequest;

class RemoteInvalidator implements InvalidatorInterface
{
    private $purgeUrls;
    private $httpAdapter;

    public function __construct(array $purgeUrls = array(), HttpAdapter $httpAdapter)
    {
        $this->purgeUrls = $purgeUrls;
        $this->httpAdapter = $httpAdapter;
    }

    public function invalidateTags(array $tags)
    {

        $request = array(
            'http' =>
            array(
                'method'  => TagSubscriber::INVALIDATE,
            )
        );
        $context = stream_context_create($request);

        foreach ($this->purgeUrls as $purgeUrl) {
            $request = new InvalidationRequest(TagSubscriber::INVALIDATE, $purgeUrl, [
                TaggedCache::HEADER_TAGS => json_encode($tags)
            ]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws MissingHostException If a relative path is queued for purge/
     *                              refresh and no base URL is set
     *
     */
    protected function queueRequest($method, $url, array $headers = [])
    {

        parent::queueRequest($method, $url, $headers);
    }
}
