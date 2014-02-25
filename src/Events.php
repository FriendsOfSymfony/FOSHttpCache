<?php

namespace FOS\HttpCache;

/**
 * Events thrown by the FOSHttpCache library
 */
final class Events
{
    const PROXY_UNREACHABLE_ERROR = 'fos_http_cache.error.proxy_unreachable';
    const PROXY_RESPONSE_ERROR    = 'fos_http_cache.error.response';
}
