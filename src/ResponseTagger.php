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
use FOS\HttpCache\TagHeaderFormatter\TagHeaderParser;
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
    private array $options;

    private TagHeaderFormatter $headerFormatter;

    /**
     * @var string[]
     */
    private array $tags = [];

    /**
     * Create the response tagger with a tag header formatter and options.
     *
     * Supported options are:
     *
     * - header_formatter (TagHeaderFormatter) Default: CommaSeparatedTagHeaderFormatter with default header name
     * - strict (bool) Default: false. If set to true, throws exception when adding empty tags
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
     */
    public function getTagsHeaderName(): string
    {
        return $this->headerFormatter->getTagsHeaderName();
    }

    /**
     * Get the value for the HTTP tag header.
     *
     * This concatenates all tags and ensures correct encoding.
     *
     * @return string|string[]
     */
    public function getTagsHeaderValue(): string|array
    {
        return $this->headerFormatter->getTagsHeaderValue($this->tags);
    }

    /**
     * Split the tag header into a list of tags.
     *
     * @param string|string[] $headers
     *
     * @return string[]
     */
    protected function parseTagsHeaderValue(array|string $headers): array
    {
        if ($this->headerFormatter instanceof TagHeaderParser) {
            return $this->headerFormatter->parseTagsHeaderValue($headers);
        }

        return array_merge(...array_map(static function ($header) {
            return explode(',', $header);
        }, $headers));
    }

    /**
     * Check whether the tag handler has any tags to set on the response.
     */
    public function hasTags(): bool
    {
        return 0 < count($this->tags);
    }

    /**
     * Add tags to be set on the response.
     *
     * This must be called before any HTTP response is sent to the client.
     *
     * @param string[] $tags List of tags to add
     *
     * @throws InvalidTagException
     */
    public function addTags(array $tags): self
    {
        $filtered = array_filter($tags, 'is_string');
        $filtered = array_filter($filtered, 'strlen');

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
    public function clear(): void
    {
        $this->tags = [];
    }

    /**
     * Set tags on a response and then clear the tags.
     *
     * @param ResponseInterface $response Original response
     * @param bool              $replace  Whether to replace the current tags
     *                                    on the response or add them additionally
     */
    public function tagResponse(ResponseInterface $response, bool $replace = false): ResponseInterface
    {
        if (!$this->hasTags()) {
            return $response;
        }

        $tagsHeaderValue = $this->getTagsHeaderValue();
        $this->clear();

        // withHeader accepts a single header or an array of multiple headers.
        if ($replace) {
            return $response->withHeader($this->getTagsHeaderName(), $tagsHeaderValue);
        }

        return $response->withAddedHeader($this->getTagsHeaderName(), $tagsHeaderValue);
    }
}
