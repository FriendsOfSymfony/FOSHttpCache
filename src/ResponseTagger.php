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
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Service for Response cache tagging.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 * @author André Rømcke <ar@ez.no>
 * @author Wicliff Wolda <wicliff.wolda@gmail.com>
 */
class ResponseTagger
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var TagCapable
     */
    private $proxyClient;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * Create the response tagger with a tag capable proxy client and options.
     *
     * Supported options are:
     *
     * - strict (bool) Default: false. If set to true, throws exception when adding empty tags
     *
     * @param TagCapable $proxyClient
     * @param array      $options
     */
    public function __construct(TagCapable $proxyClient, array $options = array())
    {
        $this->proxyClient = $proxyClient;

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'strict' => false,
        ));

        $resolver->setAllowedTypes('strict', 'bool');

        $this->options = $resolver->resolve($options);
    }

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName()
    {
        return $this->proxyClient->getTagsHeaderName();
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
        return $this->proxyClient->getTagsHeaderValue($this->tags);
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

        $this->tags = array_merge($this->tags, $filtered);

        return $this;
    }

    /**
     * Set tags on a response.
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

        if ($replace) {
            return $response->withHeader($this->getTagsHeaderName(), $this->getTagsHeaderValue());
        }

        return $response->withAddedHeader($this->getTagsHeaderName(), $this->getTagsHeaderValue());
    }
}
