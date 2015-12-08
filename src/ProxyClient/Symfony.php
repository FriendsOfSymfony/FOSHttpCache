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
use Http\Message\MessageFactory;
use Http\Adapter\HttpAdapter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FOS\HttpCache\ProxyClient\Invalidation\TagsInterface;
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use FOS\HttpCache\Tag\Manager\Symfony as SymfonyTagManager;
use FOS\HttpCache\Tag\Manager\NullTagManager;

/**
 * Symfony HttpCache invalidator.
 *
 * Additional constructor options:
 * - purge_method:         HTTP method that identifies purge requests.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 */
class Symfony extends AbstractProxyClient implements PurgeInterface, RefreshInterface, TagsInterface
{
    const HTTP_METHOD_REFRESH = 'GET';
    const HTTP_METHOD_INVALIDATE = 'INVALIDATE';

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
    public function __construct(
        array $servers,
        $baseUrl = null,
        HttpAdapter $httpAdapter = null,
        $options = []
    ) {
        parent::__construct($servers, $baseUrl, $httpAdapter);

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'purge_method' => PurgeSubscriber::DEFAULT_PURGE_METHOD,
            'tags_invalidator' => new NullTagManager()
        ]);

        $this->options = $resolver->resolve($options);
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

    /**
     * {@inheritDoc}
     */
    public function invalidateTags(array $tags)
    {
        $this->options['tags_invalidator']->invalidateTags($tags);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTagsHeaderValue(array $tags)
    {
        return json_decode($tags);
    }

    /**
     * {@inheritDoc}
     */
    public function getTagsHeaderName()
    {
        return SymfonyTagManager::HEADER_TAGS;
    }
}
