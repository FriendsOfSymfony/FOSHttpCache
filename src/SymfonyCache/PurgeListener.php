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

/**
 * Purge handler for the symfony built-in HttpCache.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
class PurgeListener extends AccessControlledListener
{
    const DEFAULT_PURGE_METHOD = 'PURGE';

    /**
     * The purge method to use.
     *
     * @var string
     */
    private $purgeMethod;

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

        $this->purgeMethod = $this->getOptionsResolver()->resolve($options)['purge_method'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_INVALIDATE => 'handlePurge',
        ];
    }

    /**
     * Look at unsafe requests and handle purge requests.
     *
     * Prevents access when the request comes from a non-authorized client.
     *
     * @param CacheEvent $event
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

        // because symfony cache respects the scheme when caching and purging, we need to invalidate both variants
        $uri = $request->getUri();
        $uri2 = 0 === strpos($uri, 'https://')
            ? preg_replace('https', 'http', $uri, 1)
            : preg_replace('http', 'https', $uri, 1);
        $store = $event->getKernel()->getStore();
        $response = new Response();

        if ($store->purge($uri)
            || $store->purge($uri2)
        ) {
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
        $resolver->setAllowedTypes('purge_method', 'string');

        return $resolver;
    }
}
