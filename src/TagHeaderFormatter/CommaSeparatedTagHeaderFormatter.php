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
class CommaSeparatedTagHeaderFormatter implements TagHeaderFormatter
{
    /**
     * @var string
     */
    private $headerName;

    /**
     * CommaSeparatedTagHeaderFormatter constructor.
     *
     * @param string $headerName
     */
    public function __construct($headerName = TagHeaderFormatter::DEFAULT_HEADER_NAME)
    {
        $this->headerName = $headerName;
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
        return implode(',', $tags);
    }
}
