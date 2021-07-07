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

use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;

/**
 * NGINX HTTP cache invalidator.
 *
 * Additional constructor options:
 * - purge_location: Path location that triggers purging. String, or set to
 *   boolean false for same location purging.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 */
class Nginx extends HttpProxyClient implements PurgeCapable, RefreshCapable
{
    public const HTTP_METHOD_PURGE = 'PURGE';

    public const HTTP_METHOD_REFRESH = 'GET';

    public const HTTP_HEADER_REFRESH = 'X-Refresh';

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
     * @param string $url URL
     *
     * @return string Rewritten URL
     */
    private function buildPurgeUrl($url)
    {
        if (!$this->options['purge_location']) {
            return $url;
        }

        $urlParts = parse_url($url);

        if (isset($urlParts['host'])) {
            $pathStartAt = strpos($url, $urlParts['path']);
            $purgeUrl = substr($url, 0, $pathStartAt).$this->options['purge_location'].substr($url, $pathStartAt);
        } else {
            $purgeUrl = $this->options['purge_location'].$url;
        }

        return $purgeUrl;
    }
}
