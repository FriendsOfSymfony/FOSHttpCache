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
 * Additional constructor options:
 * - purge_location: Path location that triggers purging. String, or set to
 *   boolean false for same location purging.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 */
class Nginx extends HttpProxyClient implements PurgeInterface, RefreshInterface
{
    const HTTP_METHOD_PURGE = 'PURGE';
    const HTTP_METHOD_REFRESH = 'GET';
    const HTTP_HEADER_REFRESH = 'X-Refresh';

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
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();
        $resolver->setDefaults(['purge_location' => false]);

        return $resolver;
    }

    /**
     * Create the correct URL to purge a resource.
     *
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
