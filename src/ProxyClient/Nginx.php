<?php

namespace FOS\HttpCache\ProxyClient;

use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use Guzzle\Http\ClientInterface;

/**
 * Nginx HTTP cache invalidator.
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
    protected $purgeLocation;

    /**
     * {@inheritdoc}
     *
     * @param array           $servers Caching proxy server hostnames or IP addresses,
     *                                 including port if not port 80.
     *                                 E.g. array('127.0.0.1:6081')
     * @param string          $baseUrl Default application hostname, optionally
     *                                 including base URL, for purge and refresh
     *                                 requests (optional). This is required if
     *                                 you purge and refresh paths instead of
     *                                 absolute URLs.
     * @param string          $purgeLocation Path that triggers purge (optional).
     * @param ClientInterface $client  HTTP client (optional). If no HTTP client
     *                                 is supplied, a default one will be
     *                                 created.
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
    public function purge($url)
    {
        $purgeUrl = str_replace(
            $this->client->getBaseUrl(),
            $this->client->getBaseUrl().$this->purgeLocation,
            $url
        );

        $this->queueRequest(self::HTTP_METHOD_PURGE, $purgeUrl);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedSchemes()
    {
        return array('http', 'https');
    }
}
