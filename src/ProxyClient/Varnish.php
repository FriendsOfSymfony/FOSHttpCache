<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient;

use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\Invalidation\TagsInterface;
use Http\Message\MessageFactory;

/**
 * Varnish HTTP cache invalidator.
 *
 * Additional constructor options:
 * - tags_header   Header for tagging responses, defaults to X-Cache-Tags
 * - header_length Maximum header length, defaults to 7500 bytes
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish extends AbstractVarnishClient implements BanInterface, PurgeInterface, RefreshInterface, TagsInterface
{
    const HTTP_METHOD_BAN          = 'BAN';
    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';

    /**
     * Map of default headers for ban requests with their default values.
     *
     * @var array
     */
    private $defaultBanHeaders = [
        self::HTTP_HEADER_HOST         => self::REGEX_MATCH_ALL,
        self::HTTP_HEADER_URL          => self::REGEX_MATCH_ALL,
        self::HTTP_HEADER_CONTENT_TYPE => self::REGEX_MATCH_ALL
    ];

    /**
     * Set the default headers that get merged with the provided headers in self::ban().
     *
     * @param array $headers Hashmap with keys being the header names, values
     *                       the header values.
     */
    public function setDefaultBanHeaders(array $headers)
    {
        $this->defaultBanHeaders = $headers;
    }

    /**
     * Add or overwrite a default ban header.
     *
     * @param string $name  The name of that header
     * @param string $value The content of that header
     */
    public function setDefaultBanHeader($name, $value)
    {
        $this->defaultBanHeaders[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $escapedTags = array_map('preg_quote', $this->escapeTags($tags));

        if (mb_strlen(implode('|', $escapedTags)) >= $this->options['header_length']) {
            /*
             * estimate the amount of tags to invalidate by dividing the max
             * header length by the largest tag (minus 1 for the implode character)
             */
            $tagsize = max(array_map('mb_strlen', $escapedTags));
            $elems = floor($this->options['header_length'] / ($tagsize - 1)) ? : 1;
        } else {
            $elems = count($escapedTags);
        }

        foreach (array_chunk($escapedTags, $elems) as $tagchunk) {
            $tagExpression = sprintf('(%s)(,.+)?$', implode('|', $tagchunk));
            $this->ban([$this->options['tags_header'] => $tagExpression]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderValue(array $tags)
    {
        return array_unique($this->escapeTags($tags));
    }

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName()
    {
        return $this->options['tags_header'];
    }

    /**
     * Get the maximum HTTP header length.
     *
     * @return int
     */
    public function getHeaderLength()
    {
        return $this->options['header_length'];
    }

    /**
     * {@inheritdoc}
     */
    public function ban(array $headers)
    {
        $headers = array_merge(
            $this->defaultBanHeaders,
            $headers
        );

        $this->queueRequest(self::HTTP_METHOD_BAN, '/', $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function banPath($path, $contentType = null, $hosts = null)
    {
        if (is_array($hosts)) {
            $hosts = $this->createHostsRegex($hosts);
        }

        $headers = [
            self::HTTP_HEADER_URL => $path,
        ];

        if ($contentType) {
            $headers[self::HTTP_HEADER_CONTENT_TYPE] = $contentType;
        }
        if ($hosts) {
            $headers[self::HTTP_HEADER_HOST] = $hosts;
        }

        return $this->ban($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        $headers = array_merge($headers, ['Cache-Control' => 'no-cache']);
        $this->queueRequest(self::HTTP_METHOD_REFRESH, $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions()
    {
        $resolver = parent::getDefaultOptions();
        $resolver->setDefaults(['tags_header' => 'X-Cache-Tags']);
        $resolver->setDefaults(['header_length' => 7500]);

        return $resolver;
    }

    /**
     * Build the invalidation request and validate it.
     *
     * {@inheritdoc}
     *
     * @throws MissingHostException If a relative path is queued for purge/
     *                              refresh and no base URL is set
     *
     */
    protected function queueRequest($method, $url, array $headers = [])
    {
        $request = $this->messageFactory->createRequest($method, $url, $headers);

        if (self::HTTP_METHOD_BAN !== $method
            && null === $this->options['base_uri']
            && !$request->getHeaderLine('Host')
        ) {
            throw MissingHostException::missingHost($url);
        }

        $this->httpAdapter->invalidate($request);
    }
}
