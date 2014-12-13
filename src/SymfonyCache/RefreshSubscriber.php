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

/**
 * Refresh handler for the symfony built-in HttpCache.
 *
 * To use this handler, make sure that your HttpCache makes the fetch method
 * public.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
class RefreshSubscriber extends AccessControlledSubscriber
{
    /**
     * When creating this subscriber, you can configure a number of options.
     *
     * - refresh_client_matcher: RequestMatcher to identify valid refresh clients.
     * - refresh_client_ips:     IP or array of IPs that are allowed to refresh.
     *
     * Only set one of refresh_client_ips and refresh_client_matcher.
     *
     * @param array $options Options to overwrite the default options
     *
     * @throws \InvalidArgumentException if unknown keys are found in $options
     */
    public function __construct(array $options = array())
    {
        $extra = array_diff(array_keys($options), array('refresh_client_matcher', 'refresh_client_ips'));
        if (count($extra)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported refresh configuration option(s) "%s"',
                implode(', ', $extra)
            ));
        }

        parent::__construct(
            isset($options['refresh_client_matcher']) ? $options['refresh_client_matcher'] : null,
            isset($options['refresh_client_ips']) ? $options['refresh_client_ips'] : null
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_HANDLE => 'handleRefresh',
        );
    }

    /**
     * Look at safe requests and handle refresh requests.
     *
     * Ignore refresh to let normal lookup happen when the request comes from
     * a non-authorized client.
     *
     * @param CacheEvent $event
     */
    public function handleRefresh(CacheEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->isMethodSafe()
            || !$request->isNoCache()
            || !$this->isRequestAllowed($request)
        ) {
            return;
        }

        $event->setResponse(
            $event->getKernel()->fetch($request)
        );
    }
}
