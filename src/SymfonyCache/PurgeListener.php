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
use Toflar\Psr6HttpCacheStore\ClearableInterface;

/**
 * Purge handler for the symfony built-in HttpCache.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 *
 * {@inheritdoc}
 */
class PurgeListener extends AccessControlledListener
{
    public const DEFAULT_PURGE_METHOD = 'PURGE';

    public const DEFAULT_CLEAR_CACHE_HEADER = 'Clear-Cache';

    /**
     * The purge method to use.
     *
     * @var string
     */
    private $purgeMethod;

    /**
     * The clear cache header to use.
     *
     * @var string
     */
    private $clearCacheHeader;

    /**
     * When creating the purge listener, you can configure an additional option.
     *
     * - purge_method: HTTP method that identifies purge requests.
     *
     * @param array $options Options to overwrite the default options
     *
     * @throws \InvalidArgumentException if unknown keys are found in $options
     *
     * @see AccessControlledListener::__construct
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $options = $this->getOptionsResolver()->resolve($options);
        $this->purgeMethod = $options['purge_method'];
        $this->clearCacheHeader = $options['clear_cache_header'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_INVALIDATE => 'handlePurge',
        ];
    }

    /**
     * Look at unsafe requests and handle purge requests.
     *
     * Prevents access when the request comes from a non-authorized client.
     */
    public function handlePurge(CacheEvent $event)
    {
        $request = $event->getRequest();
        if ($this->purgeMethod !== $request->getMethod()) {
            return;
        }

        if (!$this->isRequestAllowed($request)) {
            $event->setResponse(new Response('', 400));

            return;
        }

        $response = new Response();
        $store = $event->getKernel()->getStore();

        // Purge whole cache
        if ($request->headers->has($this->clearCacheHeader)) {
            if (!$store instanceof ClearableInterface) {
                $response->setStatusCode(400);
                $response->setContent('Store must be an instance of '.ClearableInterface::class.'. Please check your proxy configuration.');
                $event->setResponse($response);

                return;
            }

            $store->clear();

            $response->setStatusCode(200, 'Purged');
            $event->setResponse($response);

            return;
        }

        if ($store->purge($request->getUri())) {
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
        $resolver->setDefault('purge_method', static::DEFAULT_PURGE_METHOD);
        $resolver->setDefault('clear_cache_header', static::DEFAULT_CLEAR_CACHE_HEADER);
        $resolver->setAllowedTypes('purge_method', 'string');
        $resolver->setAllowedTypes('clear_cache_header', 'string');

        return $resolver;
    }
}
