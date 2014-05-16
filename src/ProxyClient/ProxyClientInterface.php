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

use FOS\HttpCache\Exception\ExceptionCollection;

/**
 * An HTTP caching reverse proxy client
 *
 * Implementations should implement at least one of the Invalidation interfaces.
 */
interface ProxyClientInterface
{
    /**
     * Send all pending invalidation requests.
     *
     * @return int The number of cache invalidations performed per caching server.
     *
     * @throws ExceptionCollection If any errors occurred during flush.
     */
    public function flush();
}
