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
 * Service for tag header formatting.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class CommaSeparatedTagHeaderFormatter implements TagHeaderFormatter, TagHeaderParser
{
    /**
     * @var string
     */
    private $headerName;

    /**
     * @var string
     */
    private $glue;

    /**
     * @param string $headerName
     * @param string $glue       Separator character for the tag header
     */
    public function __construct($headerName = TagHeaderFormatter::DEFAULT_HEADER_NAME, $glue = ',')
    {
        $this->headerName = $headerName;
        $this->glue = $glue;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderName()
    {
        return $this->headerName;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderValue(array $tags)
    {
        return implode($this->glue, $tags);
    }

    public function parseTagsHeaderValue($tags): array
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        return array_merge(...array_map(function ($tagsFragment) {
            return explode($this->glue, $tagsFragment);
        }, $tags));
    }
}
