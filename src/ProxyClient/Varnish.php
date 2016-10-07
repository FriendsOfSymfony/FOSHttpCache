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

use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\Invalidation\TagsInterface;

/**
 * Varnish HTTP cache invalidator.
 *
 * Additional constructor options:
 * - tags_header         Header for tagging responses, defaults to X-Cache-Tags
 * - header_length       Maximum header length, defaults to 7500 bytes
 * - default_ban_headers Map of headers that are set on each ban request,
 *                       merged with the built-in headers
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish extends HttpProxyClient implements BanInterface, PurgeInterface, RefreshInterface, TagsInterface
{
    const HTTP_METHOD_BAN = 'BAN';
    const HTTP_METHOD_PURGE = 'PURGE';
    const HTTP_METHOD_REFRESH = 'GET';
    const HTTP_HEADER_HOST = 'X-Host';
    const HTTP_HEADER_URL = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';
    const DEFAULT_HTTP_HEADER_CACHE_TAGS = 'X-Cache-Tags';

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
            $elems = floor($this->options['header_length'] / ($tagsize - 1)) ?: 1;
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
     * {@inheritdoc}
     */
    public function ban(array $headers)
    {
        $headers = array_merge(
            $this->options['default_ban_headers'],
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
            if (!count($hosts)) {
                throw new InvalidArgumentException('Either supply a list of hosts or null, but not an empty array.');
            }
            $hosts = '^('.implode('|', $hosts).')$';
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
        $resolver->setDefaults([
            'tags_header' => self::DEFAULT_HTTP_HEADER_CACHE_TAGS,
            'header_length' => 7500,
            'default_ban_headers' => [],
        ]);
        $resolver->setNormalizer('default_ban_headers', function ($resolver, $specified) {
            return array_merge(
                [
                    self::HTTP_HEADER_HOST => self::REGEX_MATCH_ALL,
                    self::HTTP_HEADER_URL => self::REGEX_MATCH_ALL,
                    self::HTTP_HEADER_CONTENT_TYPE => self::REGEX_MATCH_ALL,
                ],
                $specified
            );
        });

        return $resolver;
    }
}
