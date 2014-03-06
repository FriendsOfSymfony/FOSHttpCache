<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Invalidation\Method\PurgeInterface;
use Guzzle\Http\ClientInterface;

/**
 * Nginx HTTP cache invalidator for separate location setup.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 *
 */
class NginxSeparateLocation extends Nginx implements PurgeInterface
{

    const HTTP_METHOD_PURGE        = 'GET';

    /**
     * Path location that triggers purging.
     * It depends on your configuration. 
     *
     * @var string
     */
    protected $purge_location;

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
     * @param string          $purge_location Path location that trigger purging. 
     *                                 It depends on your configuration.
     */

    public function __construct(
	array $servers, 
	$baseUrl = null, 
	ClientInterface $client = null, 
	$purge_location = 'purge'
    ) {
        $this->purge_location = $purge_location;
        parent::__construct($servers, $baseUrl, $client);
    ) }

    /**
     * {@inheritdoc}
     */
    public function purge($url)
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $this->purge_location.$url);

        return $this;
    }

}
