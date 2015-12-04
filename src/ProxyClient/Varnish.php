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
use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\Invalidation\TagsInterface;
use FOS\HttpCache\ProxyClient\Request\InvalidationRequest;
use FOS\HttpCache\ProxyClient\Request\RequestQueue;
use Http\Adapter\HttpAdapter;

/**
 * Varnish HTTP cache invalidator.
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish extends AbstractProxyClient implements BanInterface, PurgeInterface, RefreshInterface, TagsInterface
{
    const HTTP_METHOD_BAN          = 'BAN';
    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_HOST         = 'X-Host';
    const HTTP_HEADER_URL          = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';

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
     * Has a base URI been set?
     *
     * @var bool
     */
    private $baseUriSet;

    /**
     * @var string
     */
    private $tagsHeader;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array $servers,
        $baseUri = null,
        HttpAdapter $httpAdapter = null,
        $tagsHeader = 'X-Cache-Tags'
    ) {
        parent::__construct($servers, $baseUri, $httpAdapter);
        $this->baseUriSet = $baseUri !== null;
        $this->tagsHeader = $tagsHeader;
    }

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
        $tagExpression = sprintf('(%s)(,.+)?$', implode('|', array_map('preg_quote', $this->escapeTags($tags))));

        return $this->ban([$this->tagsHeader => $tagExpression]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTagsHeaderValue(array $tags)
    {
        return implode(',', array_unique($this->escapeTags($tags)));
    }

    /**
     * Get the HTTP header name that will hold cache tags.
     *
     * @return string
     */
    public function getTagsHeaderName()
    {
        return $this->tagsHeader;
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
            if (!count($hosts)) {
                throw new InvalidArgumentException('Either supply a list of hosts or null, but not an empty array.');
            }
            $hosts = '^('.join('|', $hosts).')$';
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
     *
     * @throws MissingHostException If a relative path is queued for purge/
     *                              refresh and no base URL is set
     *
     */
    protected function queueRequest($method, $url, array $headers = [])
    {
        $request = new InvalidationRequest($method, $url, $headers);

        if (self::HTTP_METHOD_BAN !== $method
            && !$this->baseUriSet
            && !$request->getHeaderLine('Host')
        ) {
            throw MissingHostException::missingHost($url);
        }

        parent::queueRequest($method, $url, $headers);
    }
}
