<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Invalidation\Method\PurgeInterface;

/**
 * Nginx HTTP cache invalidator for separate location setup.
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 *
 */
class NginxSeparateLocation extends Nginx implements PurgeInterface
{
    /**
     * The path that triggers purging
     *
     * @var string
     */
    protected $purge;

    /**
     * {@inheritdoc}
     */
    public function purge($url)
    {
        $this->queueRequest(self::HTTP_METHOD_GET, $url);

        return $this;
    }

}
