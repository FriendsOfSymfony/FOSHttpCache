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
use Psr\Http\Message\RequestInterface;

/**
 * Queue and send HTTP invalidation requests.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
interface Dispatcher
{
    /**
     * Queue invalidation request.
     *
     * @param bool $validateHost If false, do not validate
     *                           that we either have a base
     *                           uri or the invalidation
     *                           request specifies the host
     */
    public function invalidate(RequestInterface $invalidationRequest, $validateHost = true);

    /**
     * Send all pending invalidation requests and make sure the requests have
     * terminated and gather exceptions.
     *
     * @return int The number of cache invalidations performed per caching
     *             server
     *
     * @throws ExceptionCollection If any errors occurred during flush
     */
    public function flush();
}
