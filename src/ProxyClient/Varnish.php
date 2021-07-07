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
use Symfony\Component\OptionsResolver\Options;

/**
 * Varnish HTTP cache invalidator.
 *
 * Additional constructor options:
 * - tag_mode            Whether to use ban or the xkey extension for cache tagging.
 * - tags_header         Header for sending tag invalidation requests to
 *                       Varnish, if tag_mode is ban, defaults to X-Cache-Tags,
 *                       otherwise defaults to xkey-softpurge.
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
    public const HTTP_METHOD_BAN = 'BAN';

    public const HTTP_METHOD_PURGE = 'PURGE';

    public const HTTP_METHOD_PURGEKEYS = 'PURGEKEYS';

    public const HTTP_METHOD_REFRESH = 'GET';

    public const HTTP_HEADER_HOST = 'X-Host';

    public const HTTP_HEADER_URL = 'X-Url';

    public const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';

    public const TAG_BAN = 'ban';

    public const TAG_XKEY = 'purgekeys';

    /**
     * Default name of the header used to invalidate content with specific tags.
     *
     * This happens to be the same as TagHeaderFormatter::DEFAULT_HEADER_NAME
     * but does not technically need to be the same.
     *
     * @var string
     */
    public const DEFAULT_HTTP_HEADER_CACHE_TAGS = 'X-Cache-Tags';

    public const DEFAULT_HTTP_HEADER_CACHE_XKEY = 'xkey-softpurge';

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $banMode = self::TAG_BAN === $this->options['tag_mode'];

        // If using BAN's, escape each tag
        if ($banMode) {
            $tags = array_map('preg_quote', $this->escapeTags($tags));
        }

        $chunkSize = $this->determineTagsPerHeader($tags, $banMode ? '|' : ' ');

        foreach (array_chunk($tags, $chunkSize) as $tagchunk) {
            if ($banMode) {
                $this->invalidateByBan($tagchunk);
            } else {
                $this->invalidateByPurgekeys($tagchunk);
            }
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
            'tag_mode' => self::TAG_BAN,
            'header_length' => 7500,
            'default_ban_headers' => [],
        ]);
        $resolver->setDefault('tags_header', function (Options $options) {
            if (self::TAG_BAN === $options['tag_mode']) {
                return self::DEFAULT_HTTP_HEADER_CACHE_TAGS;
            }

            return self::DEFAULT_HTTP_HEADER_CACHE_XKEY;
        });
        $resolver->setAllowedValues('tag_mode', [self::TAG_BAN, self::TAG_XKEY]);
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

    private function invalidateByBan(array $tagchunk)
    {
        $tagExpression = sprintf('(^|,)(%s)(,|$)', implode('|', $tagchunk));
        $this->ban([$this->options['tags_header'] => $tagExpression]);
    }

    private function invalidateByPurgekeys(array $tagchunk)
    {
        $this->queueRequest(
            self::HTTP_METHOD_PURGEKEYS,
            '/',
            [$this->options['tags_header'] => implode(' ', $tagchunk)],
            false
        );
    }
}
