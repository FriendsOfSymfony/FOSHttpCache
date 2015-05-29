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
use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\SymfonyCache\PurgeSubscriber;
use Guzzle\Http\ClientInterface;
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
     * The options configured in the constructor argument or default values.
     *
     * @var array
     */
    private $options;

    /**
     * {@inheritDoc}
     *
     * When creating the client, you can configure options:
     *
     * - purge_method:         HTTP method that identifies purge requests.
     *
     * @param array $options The purge_method that should be used.
     */
    public function __construct(array $servers, $baseUrl = null, ClientInterface $client = null, array $options = array())
    {
        parent::__construct($servers, $baseUrl, $client);

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'purge_method' => PurgeSubscriber::DEFAULT_PURGE_METHOD,
        ));

        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = array())
    {
        $this->queueRequest($this->options['purge_method'], $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($url, array $headers = array())
    {
        $headers = array_merge($headers, array('Cache-Control' => 'no-cache'));
        $this->queueRequest(self::HTTP_METHOD_REFRESH, $url, $headers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedSchemes()
    {
        return array('http', 'https');
    }
}
