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
 * Interface for tag header formatting.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
interface TagHeaderFormatterInterface
{
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
     * @param array $tags
     *
     * @return string
     */
    public function getTagsHeaderValue(array $tags);
}
