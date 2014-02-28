<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\Invalidation\Method\BanInterface;
use FOS\HttpCache\Invalidation\Method\PurgeInterface;
use FOS\HttpCache\Invalidation\Method\RefreshInterface;

/**
 * Varnish HTTP cache invalidator.
 *
 * @author David de Boer <david@driebit.nl>
 */
class Varnish extends AbstractCacheProxy implements BanInterface, PurgeInterface, RefreshInterface
{
    const HTTP_METHOD_BAN          = 'BAN';
    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_HOST         = 'X-Host';
    const HTTP_HEADER_URL          = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';
    const HTTP_HEADER_CACHE        = 'X-Cache-Tags';

    /**
     * Map of default headers for ban requests with their default values.
     *
     * @var array
     */
    protected $defaultBanHeaders = array(
        self::HTTP_HEADER_HOST         => self::REGEX_MATCH_ALL,
        self::HTTP_HEADER_URL          => self::REGEX_MATCH_ALL,
        self::HTTP_HEADER_CONTENT_TYPE => self::REGEX_MATCH_ALL
    );

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
                throw new \InvalidArgumentException('Either supply a list of hosts or null, but not an empty array.');
            }
            $hosts = '^('.join('|', $hosts).')$';
        }

        $headers = array(
            self::HTTP_HEADER_URL => $path,
        );

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
    public function purge($url)
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = array())
    {
        $headers = array_merge($headers, array('Cache-Control' => 'no-cache'));
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
    protected function createRequest($method, $url, array $headers = array())
    {
        $request = parent::createRequest($method, $url, $headers);

        // For purge and refresh, add a host header to the request if it hasn't
        // been set
        if (self::HTTP_METHOD_BAN !== $method
            && '' == $request->getHeader('Host')
        ) {
            throw new MissingHostException($url);
        }

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedSchemes()
    {
        return array('http');
    }
}
