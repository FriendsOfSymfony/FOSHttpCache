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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Purge handler for the symfony built-in HttpCache.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
class PurgeSubscriber extends AccessControlledSubscriber
{
    const DEFAULT_PURGE_METHOD = 'PURGE';

    /**
     * The options configured in the constructor argument or default values.
     *
     * @var array
     */
    private $options = array();

    /**
     * When creating this subscriber, you can configure a number of options.
     *
     * - purge_method:         HTTP method that identifies purge requests.
     * - purge_client_matcher: RequestMatcher to identify valid purge clients.
     * - purge_client_ips:     IP or array of IPs that are allowed to purge.
     *
     * Only set one of purge_client_ips and purge_client_matcher.
     *
     * @param array $options Options to overwrite the default options
     *
     * @throws \InvalidArgumentException if unknown keys are found in $options
     */
    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver();
        if (method_exists($resolver, 'setDefined')) {
            // this was only added in symfony 2.6
            $resolver->setDefined(array('purge_client_matcher', 'purge_client_ips', 'purge_method'));
        } else {
            $resolver->setOptional(array('purge_client_matcher', 'purge_client_ips', 'purge_method'));
        }
        $resolver->setDefaults(array(
            'purge_client_matcher' => null,
            'purge_client_ips' => null,
            'purge_method' => static::DEFAULT_PURGE_METHOD,
        ));

        $this->options = $resolver->resolve($options);

        parent::__construct($this->options['purge_client_matcher'], $this->options['purge_client_ips']);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_INVALIDATE => 'handlePurge',
        );
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
        if ($this->options['purge_method'] !== $request->getMethod()) {
            return;
        }

        if (!$this->isRequestAllowed($request)) {
            $event->setResponse(new Response('', 400));

            return;
        }

        $response = new Response();
        if ($event->getKernel()->getStore()->purge($request->getUri())) {
            $response->setStatusCode(200, 'Purged');
        } else {
            $response->setStatusCode(200, 'Not found');
        }
        $event->setResponse($response);
    }
}
