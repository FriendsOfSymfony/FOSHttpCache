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

use FOS\HttpCache\Exception\InvalidTagException;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Service for Response cache tagging.
 *
 * Record tags with this class and then either get the tags header or have the
 * tagger add the tags to a PSR-7 response.
 * Recorded tags are cleared after tagging a response.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 * @author André Rømcke <ar@ez.no>
 * @author Wicliff Wolda <wicliff.wolda@gmail.com>
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class ResponseTagger
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var TagHeaderFormatter
     */
    private $headerFormatter;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * Create the response tagger with a tag header formatter and options.
     *
     * Supported options are:
     *
     * - header_formatter (TagHeaderFormatter) Default: CommaSeparatedTagHeaderFormatter with default header name
     * - strict (bool) Default: false. If set to true, throws exception when adding empty tags
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            // callback to avoid instantiating the formatter when its not needed
            'header_formatter' => function (Options $options) {
                return new CommaSeparatedTagHeaderFormatter();
            },
            'strict' => false,
        ]);

        $resolver->setAllowedTypes('header_formatter', TagHeaderFormatter::class);
        $resolver->setAllowedTypes('strict', 'bool');

        $this->options = $resolver->resolve($options);
        $this->headerFormatter = $this->options['header_formatter'];
    }

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName()
    {
        return $this->headerFormatter->getTagsHeaderName();
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
        return $this->headerFormatter->getTagsHeaderValue($this->tags);
    }

    /**
     * Check whether the tag handler has any tags to set on the response.
     *
     * @return bool True if this handler will set at least one tag
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
     * @param array $tags List of tags to add
     *
     * @throws InvalidTagException
     *
     * @return $this
     */
    public function addTags(array $tags)
    {
        $filtered = array_filter($tags, 'strlen');

        if ($this->options['strict'] && array_diff($tags, $filtered)) {
            throw new InvalidTagException('Empty tags are not allowed');
        }

        $this->tags = array_unique(array_merge($this->tags, $filtered));

        return $this;
    }

    /**
     * Remove all tags that have been recorded.
     *
     * This is usually called after adding the tags header to a response. It is
     * automatically called by the tagResponse method.
     */
    public function clear()
    {
        $this->tags = [];
    }

    /**
     * Set tags on a response and then clear the tags.
     *
     * @param ResponseInterface $response Original response
     * @param bool              $replace  Whether to replace the current tags
     *                                    on the response
     *
     * @return ResponseInterface Tagged response
     */
    public function tagResponse(ResponseInterface $response, $replace = false)
    {
        if (!$this->hasTags()) {
            return $response;
        }

        $tagsHeaderValue = $this->getTagsHeaderValue();
        $this->clear();

        if ($replace) {
            return $response->withHeader($this->getTagsHeaderName(), $tagsHeaderValue);
        }

        return $response->withAddedHeader($this->getTagsHeaderName(), $tagsHeaderValue);
    }
}
