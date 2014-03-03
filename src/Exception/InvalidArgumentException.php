<?php

namespace FOS\HttpCache\Exception;

/**
 * Wrapping the base exception for FOSHttpCache.
 */
class InvalidArgumentException extends \InvalidArgumentException implements HttpCacheExceptionInterface
{
}
