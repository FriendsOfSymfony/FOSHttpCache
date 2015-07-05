<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\ProxyClient;

use FOS\HttpCache\ProxyClient\VarnishAdmin;
use FOS\HttpCache\Test\VarnishTestCase;
use FOS\HttpCache\Tests\Functional\Fixtures\BanTest;

class VarnishAdminTest extends VarnishTestCase
{
    use BanTest;

    /**
     * Get Varnish admin proxy client
     *
     * @return VarnishAdmin
     */
    protected function getProxyClient()
    {
        if (null === $this->proxyClient) {
            $this->proxyClient = new VarnishAdmin(
                '127.0.0.1',
                $this->getProxy()->getManagementPort(),
                'fos'
            );
        }

        return $this->proxyClient;
    }
}
