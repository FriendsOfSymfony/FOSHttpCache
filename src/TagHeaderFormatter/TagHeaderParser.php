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
 * The TagHeaderParser can convert the tag header into an array of tags.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
interface TagHeaderParser
{
    /**
     * Split the tag header into a list of tags.
     *
     * @param string|string[] $tags
     *
     * @return string[]
     */
    public function parseTagsHeaderValue($tags): array;
}
