<?php

namespace FOS\HttpCache\Invalidation;

use FOS\HttpCache\Invalidation\Method\PurgeInterface;

/**
 * Nginx HTTP cache invalidator for same location setup
 *
 * @author Simone Fumagalli <simone@iliveinperego.com>
 *
 */
class NginxSameLocation extends Nginx implements PurgeInterface
{
    const HTTP_METHOD_PURGE        = 'PURGE';

    /**
     * {@inheritdoc}
     */
    public function purge($url)
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url);

        return $this;
    }

}
