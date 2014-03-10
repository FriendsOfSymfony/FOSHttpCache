<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\Invalidation\Method\BanInterface;
use FOS\HttpCache\Invalidation\Method\PurgeInterface;
use FOS\HttpCache\Invalidation\Method\RefreshInterface;

/**
 * Nginx HTTP cache invalidator.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 */
class Nginx extends AbstractCacheProxy implements PurgeInterface, RefreshInterface
{

    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_HOST         = 'X-Host';
    const HTTP_HEADER_URL          = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';
    const HTTP_HEADER_CACHE        = 'X-Cache-Tags';
    const HTTP_HEADER_REFRESH      = 'X-FOS-refresh';

    /**
     * Path location that triggers purging.
     * It depends on your configuration. 
     *
     * @var mixed
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
     * @param ClientInterface $client  HTTP client (optional). If no HTTP client
     *                                 is supplied, a default one will be
     *                                 created.
     * @param string          $purgeLocation Path location that trigger purging. 
     *                                 It depends on your configuration.
     */

    public function __construct(
	array $servers, 
	$baseUrl = null, 
	ClientInterface $client = null, 
	$purgeLocation = false
    ) {
        $this->purgeLocation = $purgeLocation;
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

	if ($this->purgeLocation) {
        	$this->queueRequest(self::HTTP_METHOD_PURGE, $this->purgeLocation.$url);
	} else {
		$this->queueRequest(self::HTTP_METHOD_PURGE, $url);
	}

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
