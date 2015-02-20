<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Wrapping an error response from the caching proxy.
 */
class ProxyResponseException extends \RuntimeException implements HttpCacheExceptionInterface
{
    /**
     * @param ResponseInterface $response HTTP response
     *
     * @return ProxyResponseException
     */
    public static function proxyResponse(ResponseInterface $response)
    {
        $message = sprintf(
            '%s error response "%s" from caching proxy',
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        return new ProxyResponseException($message, 0);
    }
}
