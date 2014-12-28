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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Base class for handlers for the symfony built-in HttpCache that need access
 * control on requests.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * {@inheritdoc}
 */
abstract class AccessControlledSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestMatcher
     */
    private $requestMatcher;

    /**
     * Initializes this subscriber with either a request matcher or an IP or
     * list of IPs.
     *
     * Only one of request matcher or IPs may be a non-null value. If you use a
     * RequestMatcher, configure your IPs into it.
     *
     * If neither parameter is set, the filter is IP 127.0.0.1
     *
     * @param RequestMatcher|null  $requestMatcher Request matcher configured to only match allowed requests.
     * @param string|string[]|null $ips            IP or list of IPs that are allowed to send requests.
     *
     * @throws \InvalidArgumentException If both $requestMatcher and $ips are set.
     */
    public function __construct(RequestMatcher $requestMatcher = null, $ips = null)
    {
        if ($requestMatcher && $ips) {
            throw new \InvalidArgumentException('You may not set both a request matcher and an IP.');
        }
        if (!$requestMatcher) {
            $requestMatcher = new RequestMatcher(null, null, null, $ips ?: '127.0.0.1');
        }

        $this->requestMatcher = $requestMatcher;
    }

    /**
     * Check whether the request is allowed.
     *
     * @param Request $request The request to check.
     *
     * @return boolean Whether access is granted.
     */
    protected function isRequestAllowed(Request $request)
    {
        return $this->requestMatcher->matches($request);
    }
}
