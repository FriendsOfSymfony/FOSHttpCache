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

use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;

/**
 * LiteSpeed (OpenLiteSpeed or LiteSpeed Web Server) invalidator.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class LiteSpeed extends HttpProxyClient implements PurgeCapable, TagCapable, ClearCapable, RefreshCapable
{
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->sendPurgeRequest([
            'X-LiteSpeed-Purge' => '*',
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        // LiteSpeed only supports purging by relative URLs
        $urlParts = parse_url($url);
        $url = array_key_exists('path', $urlParts) ? $urlParts['path'] : '/';

        $this->sendPurgeRequest([
            'X-LiteSpeed-Purge' => $url,
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $this->sendPurgeRequest([
            'X-LiteSpeed-Purge' => implode(', ', preg_filter('/^/', 'tag=', $tags)),
        ]);

        return $this;
    }

    private function sendPurgeRequest(array $headers)
    {
        // TODO: LiteSpeed is likely going to hard code this URL, otherwise it has to be configurable
        $purgeEndpoint = '/';

        $this->queueRequest('PURGE', $purgeEndpoint, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        $this->purge($url);
        $this->queueRequest('GET', $url, $headers);
    }
}
