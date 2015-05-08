<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Handler;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\Exception\UnsupportedProxyOperationException;

/**
 * Handler for cache tagging.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 */
class TagHandler
{
    /**
     * @var CacheInvalidator
     */
    private $invalidator;

    /**
     * @var string
     */
    private $tagsHeader;

    /**
     * @var array
     */
    private $tags = array();

    /**
     * Constructor
     *
     * @param CacheInvalidator $invalidator The invalidator instance.
     * @param string           $tagsHeader  Header to use for tags, defaults to X-Cache-Tags.
     *
     * @throws UnsupportedProxyOperationException If CacheInvalidator does not support invalidate requests
     */
    public function __construct(CacheInvalidator $invalidator, $tagsHeader = 'X-Cache-Tags')
    {
        if (!$invalidator->supports(CacheInvalidator::INVALIDATE)) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('BAN');
        }
        $this->invalidator = $invalidator;
        $this->tagsHeader = $tagsHeader;
    }

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName()
    {
        return $this->tagsHeader;
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
        return implode(',', array_unique($this->escapeTags($this->tags)));
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

    /**
     * Invalidate cache entries that contain any of the specified tags in their
     * tag header.
     *
     * @param array $tags Cache tags
     *
     * @return $this
     */
    public function invalidateTags(array $tags)
    {
        $tagExpression = sprintf('(%s)(,.+)?$', implode('|', array_map('preg_quote', $this->escapeTags($tags))));
        $headers = array($this->tagsHeader => $tagExpression);
        $this->invalidator->invalidate($headers);

        return $this;
    }

    /**
     * Make sure that the tags are valid.
     *
     * @param array $tags The tags to escape.
     *
     * @return array Sane tags.
     */
    protected function escapeTags(array $tags)
    {
        array_walk($tags, function (&$tag) {
            $tag = str_replace(array(',', "\n"), array('_', '_'), $tag);
        });

        return $tags;
    }
}
