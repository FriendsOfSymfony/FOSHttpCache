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
     * Invalidate cache entries that contain any of the specified tags in their
     * tag header.
     *
     * @param array $tags Cache tags
     *
     * @return $this
     */
    public function invalidateTags(array $tags)
    {
        $tagExpression = sprintf('(%s)(,.+)?$', implode('|', array_map('preg_quote', $tags)));
        $headers = array($this->tagsHeader => $tagExpression);
        $this->invalidator->invalidate($headers);

        return $this;
    }
}
