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

/**
 * NGINX HTTP cache invalidator.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 */
class Nginx extends AbstractProxyClient implements PurgeInterface, RefreshInterface
{
    const HTTP_METHOD_PURGE        = 'PURGE';
    const HTTP_METHOD_REFRESH      = 'GET';
    const HTTP_HEADER_REFRESH      = 'Refresh';

    /**
     * Path location that triggers purging. If false, same location purging is
     * assumed.
     *
     * @var string|false
     */
    private $purgeLocation;

    /**
     * Set path that triggers purge
     *
     * @param string $purgeLocation
     */
    public function setPurgeLocation($purgeLocation = '')
    {
        $this->purgeLocation = (string) $purgeLocation;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        $headers = array_merge($headers, [self::HTTP_HEADER_REFRESH => '1']);
        $this->queueRequest(self::HTTP_METHOD_REFRESH, $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $purgeUrl = $this->buildPurgeUrl($url);
        $this->queueRequest(self::HTTP_METHOD_PURGE, $purgeUrl, $headers);

        return $this;
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
            $purgeUrl = $this->purgeLocation.$url;
        }

        return $purgeUrl;
    }
}
