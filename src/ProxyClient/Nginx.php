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

use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use Guzzle\Http\ClientInterface;

/**
 * NGINX HTTP cache invalidator.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 */
class Nginx extends AbstractProxyClient implements PurgeInterface, RefreshInterface
{
    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_REFRESH      = 'X-Refresh';

    /**
     * Path location that triggers purging. If false, same location purging is
     * assumed.
     *
     * @var string|false
     */
    private $purgeLocation;

    /**
     * {@inheritdoc}
     *
     * @param array           $servers       Caching proxy server hostnames or IP addresses,
     *                                       including port if not port 80.
     *                                       E.g. array('127.0.0.1:6081')
     * @param string          $baseUrl       Default application hostname, optionally
     *                                       including base URL, for purge and refresh
     *                                       requests (optional). This is required
     *                                       if you purge relative URLs and the domain
     *                                       is not part of your `proxy_cache_key`
     * @param string          $purgeLocation Path that triggers purge (optional).
     * @param ClientInterface $client        HTTP client (optional). If no HTTP client
     *                                       is supplied, a default one will be
     *                                       created.
     */
    public function __construct(
        array $servers,
        $baseUrl = null,
        $purgeLocation = '',
        ClientInterface $client = null
    ) {
        $this->purgeLocation = (string) $purgeLocation;
        parent::__construct($servers, $baseUrl, $client);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = array())
    {
        $headers = array_merge($headers, array(self::HTTP_HEADER_REFRESH => '1'));
        $this->queueRequest(self::HTTP_METHOD_REFRESH, $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = array())
    {
        $purgeUrl = $this->buildPurgeUrl($url);
        $this->queueRequest(self::HTTP_METHOD_PURGE, $purgeUrl, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedSchemes()
    {
        return array('http', 'https');
    }

    /**
     * Create the correct URL to purge a resource
     *
     * @param string $url URL
     *
     * @return string Rewritten URL
     */
    private function buildPurgeUrl($url)
    {
        if (empty($this->purgeLocation)) {
            return $url;
        }

        $urlParts = parse_url($url);

        if (isset($urlParts['host'])) {
            $pathStartAt = strpos($url, $urlParts['path']);
            $purgeUrl = substr($url, 0, $pathStartAt).$this->purgeLocation.substr($url, $pathStartAt);
        } else {
            $purgeUrl = $this->getBaseUrl().$this->purgeLocation.$url;
        }

        return $purgeUrl;
    }
}
