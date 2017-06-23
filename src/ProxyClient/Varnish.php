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
use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;

/**
 * Varnish HTTP cache invalidator.
 *
 * Additional constructor options:
 * - tags_header         Header for sending tag invalidation requests to
 *                       Varnish, defaults to X-Cache-Tags
 * - header_length       Maximum header length when invalidating tags. If there
 *                       are more tags to invalidate than fit into the header,
 *                       the invalidation request is split into several requests.
 *                       Defaults to 7500
 * - default_ban_headers Map of header name => header value that have to be set
 *                       on each ban request, merged with the built-in headers
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish extends HttpProxyClient implements BanCapable, PurgeCapable, RefreshCapable, TagCapable
{
    const HTTP_METHOD_BAN = 'BAN';
    const HTTP_METHOD_PURGE = 'PURGE';
    const HTTP_METHOD_REFRESH = 'GET';
    const HTTP_HEADER_HOST = 'X-Host';
    const HTTP_HEADER_URL = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';

    /**
     * Default name of the header used to invalidate content with specific tags.
     *
     * This happens to be the same as TagHeaderFormatter::DEFAULT_HEADER_NAME
     * but does not technically need to be the same.
     *
     * @var string
     */
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
    public function ban(array $headers)
    {
        $headers = array_merge(
            $this->options['default_ban_headers'],
            $headers
        );

        $this->queueRequest(self::HTTP_METHOD_BAN, '/', $headers, false);

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
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();
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
