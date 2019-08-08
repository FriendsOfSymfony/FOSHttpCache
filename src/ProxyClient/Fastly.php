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

use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fastly HTTP cache invalidator.
 *
 * Additional constructor options:
 * - service_identifier    Identifier for your Fastly service account.
 * - authentication_token  Token for authentication against Fastly APIs.
 *                         For full capabilities (incl ClearCapable) you'll need one with Fastly Engineer permissions.
 * - soft_purge            Boolean for doing soft purges or not on tag invalidation and url purging, default true.
 *                         Soft purges expires cache instead of hard purge, and allow grace/stale handling.
 *
 * @link https://docs.fastly.com/api/purge Fastly Purge API documentation.
 * @author Simone Fumagalli <simone.fumagalli@musement.com>
 * @author André Rømcke <andre_gpt@msn.com>
 */
class Fastly extends HttpProxyClient implements ClearCapable, PurgeCapable, RefreshCapable, TagCapable
{
    /**
     * @internal
     */
    const HTTP_METHOD_PURGE = 'PURGE';

    /**
     * @internal
     * @see https://docs.fastly.com/api/purge#purge_db35b293f8a724717fcf25628d713583 Fastly's limit on batch tag purges.
     */
    const TAG_BATCH_PURGE_LIMIT = 256;

    /**
     * {@inheritdoc}
     *
     * @see https://docs.fastly.com/api/purge#purge_db35b293f8a724717fcf25628d713583
     */
    public function invalidateTags(array $tags)
    {
        $headers = [
            'Fastly-Key' => $this->options['authentication_token'],
            'Accept' => 'application/json',
        ];

        if (true === $this->options['soft_purge']) {
            $headers['Fastly-Soft-Purge'] = 1;
        }

        // Split tag invalidations across several requests within Fastly's tag batch invalidations limits.
        foreach (\array_chunk($tags, self::TAG_BATCH_PURGE_LIMIT) as $tagChunk) {
            $this->queueRequest(
                Request::METHOD_POST,
                sprintf('/service/%s/purge', $this->options['service_identifier']),
                $headers + [
                    // Note: Can be changed to rather use json payload if queueRequest is changed to allow passing body
                    'Surrogate-Key' => implode(' ', $tagChunk),
                ],
                false
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.fastly.com/api/purge#soft_purge_0c4f56f3d68e9bed44fb8b638b78ea36
     * @see https://docs.fastly.com/guides/purging/authenticating-api-purge-requests#purging-urls-with-an-api-token
     */
    public function purge($url, array $headers = [])
    {
        $headers['Fastly-Key'] = $this->options['authentication_token'];

        if (true === $this->options['soft_purge']) {
            $headers['Fastly-Soft-Purge'] = 1;
        }

        $this->queueRequest(
            self::HTTP_METHOD_PURGE,
            $url,
            $headers,
            false
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        $headers['Fastly-Key'] = $this->options['authentication_token'];

        // First soft purge url
        $this->queueRequest(
            self::HTTP_METHOD_PURGE,
            $url,
            array_merge($headers, ['Fastly-Soft-Purge' => 1]),
            false
        );

        // Secondly make sure refresh is triggered
        $this->queueRequest(
            Request::METHOD_HEAD,
            $url,
            $headers,
            false
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.fastly.com/api/purge#purge_bee5ed1a0cfd541e8b9f970a44718546
     *
     * Warning:
     * - Does not support soft purge, for that use an "all" key.
     * - Requires a API token of a user with at least Engineer permissions.
     */
    public function clear()
    {
        $headers = [
            'Fastly-Key' => $this->options['authentication_token'],
            'Accept' => 'application/json',
        ];

        $this->queueRequest(
            Request::METHOD_POST,
            sprintf('/service/%s/purge_all', $this->options['service_identifier']),
            $headers,
            false
        );

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
            'soft_purge',
        ]);

        $resolver->setDefaults([
            'soft_purge' => true,
        ]);

        return $resolver;
    }
}
