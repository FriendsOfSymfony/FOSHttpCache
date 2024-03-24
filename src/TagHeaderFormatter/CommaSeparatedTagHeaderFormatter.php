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
    private string $headerName;

    private string $glue;

    public function __construct(string $headerName = TagHeaderFormatter::DEFAULT_HEADER_NAME, string $glue = ',')
    {
        $this->headerName = $headerName;
        $this->glue = $glue;
    }

    public function getTagsHeaderName(): string
    {
        return $this->headerName;
    }

    public function getTagsHeaderValue(array $tags): array|string
    {
        return implode($this->glue, $tags);
    }

    public function parseTagsHeaderValue(array|string $tags): array
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        return array_merge(...array_map(function ($tagsFragment) {
            return explode($this->glue, $tagsFragment);
        }, $tags));
    }
}
