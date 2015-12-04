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

use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\SymfonyCache\PurgeSubscriber;
use Http\Client\HttpAsyncClient;
use Http\Message\MessageFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Symfony HttpCache invalidator.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 */
class Symfony extends AbstractProxyClient implements PurgeInterface, RefreshInterface
{
    const HTTP_METHOD_REFRESH = 'GET';

    /**
     * {@inheritDoc}
     *
     * When creating the client, you can configure options:
     *
     * - purge_method:         HTTP method that identifies purge requests.
     *
     * @param array $options The purge_method that should be used.
     */
    public function __construct(
        array $servers,
        array $options,
        HttpAsyncClient $httpClient = null,
        MessageFactory $messageFactory = null
    ) {
        parent::__construct($servers, $options, $httpClient, $messageFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $this->queueRequest($this->options['purge_method'], $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = [])
    {
        $headers = array_merge($headers, ['Cache-Control' => 'no-cache']);
        $this->queueRequest(self::HTTP_METHOD_REFRESH, $url, $headers);

        return $this;
    }

    protected function getDefaultOptions()
    {
        $resolver = parent::getDefaultOptions();
        $resolver->setDefaults([
            'purge_method' => PurgeSubscriber::DEFAULT_PURGE_METHOD,
        ]);

        return $resolver;
    }
}
