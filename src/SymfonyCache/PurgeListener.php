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

use FOS\HttpCache\ProxyClient\Symfony;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
    const DEFAULT_PURGE_METHOD = 'PURGE';
    const DEFAULT_PURGE_TAGS_HEADER = 'X-Cache-Tags';

    /**
     * The purge method to use.
     *
     * @var string
     */
    private $purgeMethod;

    /**
     * The purge tags header to use.
     *
     * @var string
     */
    private $purgeTagsHeader;

    /**
     * When creating the purge listener, you can configure an additional option.
     *
     * - purge_method: HTTP method that identifies purge requests.
     * - purge_tags_header: HTTP header that contains cache tags to invalidate.
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
        $this->purgeTagsHeader = $this->getOptionsResolver()->resolve($options)['purge_tags_header'];
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

        $response = new Response();
        $store = $event->getKernel()->getStore();

        if ($request->headers->has($this->purgeTagsHeader)
            && $store instanceof TaggableStore
        ) {
            $tags = explode(',', $request->headers->get($this->purgeTagsHeader));

            if ($store->invalidateTags($tags)) {
                $response->setStatusCode(200, 'Purged');
            } else {
                $response->setStatusCode(200, 'Not found');
            }
        } elseif ($store->purge($request->getUri())) {
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
        $resolver->setDefault('purge_tags_header', static::DEFAULT_PURGE_TAGS_HEADER);
        $resolver->setAllowedTypes('purge_tags_header', 'string');

        return $resolver;
    }
}
