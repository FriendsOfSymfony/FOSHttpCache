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

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;
use FOS\HttpCache\SymfonyCache\HttpCacheAwareKernelInterface;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use Http\Message\RequestFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Symfony HttpCache invalidator.
 *
 * Additional constructor options:
 * - purge_method:         HTTP method that identifies purge requests.
 *
 * @author David de Boer <david@driebit.nl>
 * @author David Buchmann <mail@davidbu.ch>
 */
class Symfony extends HttpProxyClient implements PurgeCapable, RefreshCapable, TagCapable
{
    const HTTP_METHOD_REFRESH = 'GET';

    /**
     * @var array
     */
    private $queue;

    /**
     * @var HttpCacheAwareKernelInterface
     */
    private $kernel;

    /**
     * Additional parameter for (optional) kernel.
     *
     * {@inheritdoc}
     */
    public function __construct(
        HttpDispatcher $httpDispatcher,
        array $options = [],
        RequestFactory $messageFactory = null,
        HttpCacheAwareKernelInterface $kernel = null
    ) {
        parent::__construct($httpDispatcher, $options, $messageFactory);

        $this->kernel = $kernel;
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

    /**
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();
        $resolver->setDefaults([
            'purge_method' => PurgeListener::DEFAULT_PURGE_METHOD,
            'tags_method' => PurgeTagsListener::DEFAULT_TAGS_METHOD,
            'tags_header' => PurgeTagsListener::DEFAULT_TAGS_HEADER,
            'header_length' => 7500,
            'enable_kernel_routing' => false,
        ]);
        $resolver->setAllowedTypes('purge_method', 'string');
        $resolver->setAllowedTypes('tags_method', 'string');
        $resolver->setAllowedTypes('tags_header', 'string');
        $resolver->setAllowedTypes('header_length', 'int');
        $resolver->setAllowedTypes('enable_kernel_routing', 'boolean');

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (!$this->isDirectRoutingEnabled()) {
            return parent::flush();
        }

        $exceptions = new ExceptionCollection();

        foreach ($this->queue as $request) {
            try {
                $this->kernel->getHttpCache()
                    ->handle($request, HttpCacheAwareKernelInterface::MASTER_REQUEST);
            } catch (\Exception $e) {
                $exceptions->add($e);
            }
        }

        if (count($exceptions)) {
            throw $exceptions;
        }

        return count($this->queue);
    }

    /**
     * Remove/Expire cache objects based on cache tags.
     *
     * @param array $tags Tags that should be removed/expired from the cache
     *
     * @return $this
     */
    public function invalidateTags(array $tags)
    {
        $escapedTags = $this->escapeTags($tags);

        $chunkSize = $this->determineTagsPerHeader($escapedTags, ',');

        foreach (array_chunk($escapedTags, $chunkSize) as $tagchunk) {
            $this->queueRequest(
                $this->options['tags_method'],
                '/',
                [$this->options['tags_header'] => implode(',', $tagchunk)],
                false
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function queueRequest($method, $url, array $headers, $validateHost = true)
    {
        if (!$this->isDirectRoutingEnabled()) {
            return parent::queueRequest($method, $url, $headers, $validateHost);
        }

        $request = Request::create((string) $url, $method);
        $request->headers->replace($headers);

        $this->queue[sha1((string) $request)] = $request;
    }

    /**
     * @return bool
     */
    protected function isDirectRoutingEnabled()
    {
        if ($this->options['enable_kernel_routing']
            && $this->kernel instanceof HttpCacheAwareKernelInterface
        ) {
            return true;
        }

        return false;
    }
}
