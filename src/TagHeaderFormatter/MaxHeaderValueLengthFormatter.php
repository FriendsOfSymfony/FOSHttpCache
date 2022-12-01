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

use FOS\HttpCache\Exception\InvalidTagException;

/**
 * A header formatter that splits the value(s) from a given
 * other header formatter (e.g. the CommaSeparatedTagHeaderFormatter)
 * into multiple headers making sure none of the header values
 * exceeds the configured limit.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class MaxHeaderValueLengthFormatter implements TagHeaderFormatter, TagHeaderParser
{
    /**
     * @var TagHeaderFormatter
     */
    private $inner;

    /**
     * @var int
     */
    private $maxHeaderValueLength;

    /**
     * The default value of the maximum header length is 4096 because most
     * servers limit header values to 4kb.
     * HTTP messages cannot carry characters outside the ISO-8859-1 standard so they all
     * use up just one byte.
     *
     * @param int $maxHeaderValueLength
     */
    public function __construct(TagHeaderFormatter $inner, $maxHeaderValueLength = 4096)
    {
        $this->inner = $inner;
        $this->maxHeaderValueLength = $maxHeaderValueLength;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderName()
    {
        return $this->inner->getTagsHeaderName();
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderValue(array $tags)
    {
        $values = (array) $this->inner->getTagsHeaderValue($tags);
        $newValues = [[]];

        foreach ($values as $value) {
            if ($this->isHeaderTooLong($value)) {
                list($firstTags, $secondTags) = $this->splitTagsInHalves($tags);

                $newValues[] = (array) $this->getTagsHeaderValue($firstTags);
                $newValues[] = (array) $this->getTagsHeaderValue($secondTags);
            } else {
                $newValues[] = [$value];
            }
        }

        $newValues = array_merge(...$newValues);

        if (1 === count($newValues)) {
            return $newValues[0];
        }

        return $newValues;
    }

    public function parseTagsHeaderValue($tags): array
    {
        if ($this->inner instanceof TagHeaderParser) {
            return $this->inner->parseTagsHeaderValue($tags);
        }

        throw new \BadMethodCallException('The inner formatter does not implement '.TagHeaderParser::class);
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function isHeaderTooLong($value)
    {
        return mb_strlen($value) > $this->maxHeaderValueLength;
    }

    /**
     * Split an array of tags in two more or less equal sized arrays.
     *
     * @return array
     *
     * @throws InvalidTagException
     */
    private function splitTagsInHalves(array $tags)
    {
        if (1 === count($tags)) {
            throw new InvalidTagException(sprintf(
                'You configured a maximum header length of %d but the tag "%s" is too long.',
                $this->maxHeaderValueLength,
                $tags[0]
            ));
        }

        $size = ceil(count($tags) / 2);

        return array_chunk($tags, $size);
    }
}
