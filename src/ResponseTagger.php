<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache;

use FOS\HttpCache\ProxyClient\Invalidation\TagsInterface;

/**
 * Service for Response cache tagging.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 * @author André Rømcke <ar@ez.no>
 */
class ResponseTagger
{
    /**
     * @var TagsInterface
     */
    private $client;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * Constructor
     *
     * @param TagsInterface $client
     */
    public function __construct(TagsInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName()
    {
        return $this->client->getTagsHeaderName();
    }

    /**
     * Get the value for the HTTP tag header.
     *
     * This concatenates all tags and ensures correct encoding.
     *
     * @return string
     */
    public function getTagsHeaderValue()
    {
        return $this->client->getTagsHeaderValue($this->tags);
    }

    /**
     * Check whether the tag handler has any tags to set on the response.
     *
     * @return bool True if this handler will set at least one tag.
     */
    public function hasTags()
    {
        return 0 < count($this->tags);
    }

    /**
     * Add tags to be set on the response.
     *
     * This must be called before any HTTP response is sent to the client.
     *
     * @param array $tags List of tags to add.
     *
     * @return $this
     */
    public function addTags(array $tags)
    {
        $this->tags = array_merge($this->tags, $tags);

        return $this;
    }
}
