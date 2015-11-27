<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

use Symfony\Component\HttpFoundation\Request;

class RemotePurger
{
    private $purgeUrls;

    public function __construct(array $purgeUrls = array())
    {
        $this->purgeUrls = $purgeUrls;
    }

    public function invalidate(array $tags)
    {
        $request = array(
            'http' =>
            array(
                'method'  => TagSubscriber::INVALIDATE,
                'header' => sprintf('%s: %s', TaggedCache::HEADER_TAGS, json_encode($tags))
            )
        );
        $context = stream_context_create($request);

        foreach ($this->purgeUrls as $purgeUrl) {
            $contents = file_get_contents($purgeUrl, false, $context);

            $return = json_decode($contents, true);

            if (!$return) {
                throw new \RuntimeException(sprintf(
                    'Could not decode JSON response from HTTP cache: "%s"',
                    $contents
                ));
            }
        }

        return $return;
    }
}
