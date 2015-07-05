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

use FOS\HttpCache\Exception\InvalidArgumentException;

abstract class AbstractVarnishClient extends AbstractProxyClient
{
    const HTTP_HEADER_HOST         = 'X-Host';
    const HTTP_HEADER_URL          = 'X-Url';
    const HTTP_HEADER_CONTENT_TYPE = 'X-Content-Type';

    protected function createHostsRegex(array $hosts)
    {
        if (!count($hosts)) {
            throw new InvalidArgumentException('Either supply a list of hosts or null, but not an empty array.');
        }

        return '^('.join('|', $hosts).')$';
    }
}
