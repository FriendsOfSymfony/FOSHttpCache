<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\SymfonyCache;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Toflar\Psr6HttpCacheStore\Psr6StoreInterface;

/**
 * Purge tags handler for the Symfony built-in HttpCache.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 *
 * {@inheritdoc}
 */
class PurgeTagsListener extends AccessControlledListener
{
    const DEFAULT_TAGS_METHOD = 'PURGETAGS';

    const DEFAULT_TAGS_HEADER = 'X-Cache-Tags';

    /**
     * The purge tags method to use.
     *
     * @var string
     */
    private $tagsMethod;

    /**
     * The purge tags header to use.
     *
     * @var string
     */
    private $tagsHeader;

    /**
     * When creating the purge listener, you can configure an additional option.
     *
     * - tags_method: HTTP method that identifies purge tags requests.
     * - tags_header: HTTP header that contains cache tags to invalidate.
     *
     * @param array $options Options to overwrite the default options
     *
     * @throws \InvalidArgumentException if unknown keys are found in $options
     *
     * @see AccessControlledListener::__construct
     */
    public function __construct(array $options = [])
    {
        if (!interface_exists(Psr6StoreInterface::class)) {
            throw new \Exception('Cache tag invalidation only works with the toflar/psr6-symfony-http-cache-store package. See "Symfony HttpCache Configuration" in the documentation.');
        }
        parent::__construct($options);

        $options = $this->getOptionsResolver()->resolve($options);

        $this->tagsMethod = $options['tags_method'];
        $this->tagsHeader = $options['tags_header'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_INVALIDATE => 'handlePurgeTags',
        ];
    }

    /**
     * Look at unsafe requests and handle purge tags requests.
     *
     * Prevents access when the request comes from a non-authorized client.
     *
     * @param CacheEvent $event
     */
    public function handlePurgeTags(CacheEvent $event)
    {
        $request = $event->getRequest();
        if ($this->tagsMethod !== $request->getMethod()) {
            return;
        }

        if (!$this->isRequestAllowed($request)) {
            $event->setResponse(new Response('', 400));

            return;
        }

        $response = new Response();
        $store = $event->getKernel()->getStore();

        if (!$store instanceof Psr6StoreInterface) {
            $response->setStatusCode(400);
            $response->setContent('Store must be an instance of '.Psr6StoreInterface::class.'. Please check your proxy configuration.');

            $event->setResponse($response);

            return;
        }

        if (!$request->headers->has($this->tagsHeader)) {
            $response->setStatusCode(200, 'Not found');

            $event->setResponse($response);

            return;
        }

        $tags = [];

        foreach ($request->headers->get($this->tagsHeader, '', false) as $v) {
            foreach (explode(',', $v) as $tag) {
                $tags[] = $tag;
            }
        }

        if ($store->invalidateTags($tags)) {
            $response->setStatusCode(200, 'Purged');
        } else {
            $response->setStatusCode(200, 'Not found');
        }

        $event->setResponse($response);
    }

    /**
     * Add the purge_method option.
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = parent::getOptionsResolver();
        $resolver->setDefaults([
            'tags_method' => static::DEFAULT_TAGS_METHOD,
            'tags_header' => static::DEFAULT_TAGS_HEADER,
        ]);
        $resolver->setAllowedTypes('tags_method', 'string');
        $resolver->setAllowedTypes('tags_header', 'string');

        return $resolver;
    }
}
