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
use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fastly HTTP cache invalidator.
 *
 * @author Simone Fumagalli <simone.fumagalli@musement.com>
 */
class Fastly extends HttpProxyClient implements TagCapable
{
    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {

        $headers= [
            'Fastly-Key' => $this->options['authentication_token'],
            'Accept' => 'application/json'
        ];

        if (true === $this->options['soft_purge']) {
            $headers['Fastly-Soft-Purge'] = 1;
        }

        foreach ($tags as $tag) {
            $this->queueRequest(Request::METHOD_POST, sprintf("/service/%s/purge/%s", $this->options['service_identifier'], $tag), $headers, false);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();

        $resolver->setRequired([
            'authentication_token',
            'service_identifier',
            'soft_purge'
        ]);

        $resolver->setDefaults([
            'soft_purge' => true,
        ]);

        return $resolver;
    }

}
