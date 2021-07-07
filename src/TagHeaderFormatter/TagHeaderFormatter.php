<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\TagHeaderFormatter;

/**
 * The TagHeaderFormatter is used for cache tagging with HTTP headers.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
interface TagHeaderFormatter
{
    /**
     * Default name of the header to mark tags on responses.
     *
     * @var string
     */
    public const DEFAULT_HEADER_NAME = 'X-Cache-Tags';

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName();

    /**
     * Get the value for the HTTP tag header.
     *
     * This concatenates all tags and ensures correct encoding.
     *
     * @return string|string[]
     */
    public function getTagsHeaderValue(array $tags);
}
