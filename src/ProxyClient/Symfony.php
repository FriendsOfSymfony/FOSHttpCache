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
    /**
     * Method used for refresh
     */
    const HTTP_METHOD_REFRESH = 'GET';

    /**
     * Method used for invalidation
     */
    const HTTP_METHOD_INVALIDATE = 'INVALIDATE';

    /**
     * Name for HTTP header containing the tags (for both invalidation and
     * initial tagging).
     */
    const HTTP_HEADER_TAGS = 'X-Cache-Tags';

    /**
     * Name for HTTP header containing a list of tags which should be
     * invalidated.
     */
    const HTTP_HEADER_INVALIDATE_TAGS = 'X-Cache-Invalidate-Tags';

    /**
     * Header which should contain the content digest produced by the Symfony
     * HTTP cache.
     */
    const HTTP_HEADER_CONTENT_DIGEST = 'X-Content-Digest';

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
            'tags_invalidator' => new NullTagManager()
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
        return json_encode($tags, true);
    }

    /**
     * {@inheritDoc}
     */
    public function getTagsHeaderName()
    {
        return self::HTTP_HEADER_TAGS;
    }
}
