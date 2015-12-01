<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

use FOS\HttpCache\ProxyClient\Request\InvalidationRequest;
use FOS\HttpCache\ProxyClient\Symfony;
use FOS\HttpCache\SymfonyCache\TagSubscriber;

class RemoteInvalidator implements InvalidatorInterface
{
    public function invalidateTags(array $tags)
    {
        return new InvalidationRequest(Symfony::HTTP_METHOD_INVALIDATE, '/', [
            TagSubscriber::HEADER_TAGS => json_encode($tags)
        ]);
    }
}
