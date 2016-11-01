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
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class for handlers for the symfony built-in HttpCache that need access
 * control on requests.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
abstract class AccessControlledListener implements EventSubscriberInterface
{
    /**
     * @var RequestMatcher
     */
    private $clientMatcher;

    /**
     * When creating this event listener, you can configure a number of options.
     *
     * - client_matcher: RequestMatcherInterface to identify clients that are allowed to send request.
     * - client_ips:     IP or array of IPs of clients that are allowed to send requests.
     *
     * Only one of request matcher or IPs may be a non-null value. If you use a
     * RequestMatcher, configure your IPs into it.
     *
     * If neither parameter is set, the filter is IP 127.0.0.1
     *
     * @param array $options Options to overwrite the default options
     *
     * @throws \InvalidArgumentException if both client_matcher and client_ips are set or unknown keys are found in $options
     */
    public function __construct(array $options = [])
    {
        $options = $this->getOptionsResolver()->resolve($options);

        $clientMatcher = $options['client_matcher'];
        if ($clientMatcher && $options['client_ips']) {
            throw new \InvalidArgumentException('You may not set both a request matcher and an IP.');
        }
        if (!$clientMatcher) {
            $clientMatcher = new RequestMatcher(null, null, null, $options['client_ips'] ?: '127.0.0.1');
        }

        $this->clientMatcher = $clientMatcher;
    }

    /**
     * Get the options resolver for the constructor arguments.
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'client_matcher' => null,
            'client_ips' => null,
        ]);
        $resolver->setAllowedTypes('client_matcher', [RequestMatcherInterface::class, 'null']);
        $resolver->setAllowedTypes('client_ips', ['string', 'array', 'null']);

        return $resolver;
    }

    /**
     * Check whether the request is allowed.
     *
     * @param Request $request The request to check
     *
     * @return bool Whether access is granted
     */
    protected function isRequestAllowed(Request $request)
    {
        return $this->clientMatcher->matches($request);
    }
}
