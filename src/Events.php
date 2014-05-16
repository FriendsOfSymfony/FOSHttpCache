<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache;

/**
 * Events thrown by the FOSHttpCache library
 */
final class Events
{
    const PROXY_UNREACHABLE_ERROR = 'fos_http_cache.error.proxy_unreachable';
    const PROXY_RESPONSE_ERROR    = 'fos_http_cache.error.response';
}
