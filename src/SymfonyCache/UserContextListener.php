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

use FOS\HttpCache\UserContext\AnonymousRequestMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Caching proxy side of the user context handling for the symfony built-in HttpCache.
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 *
 * {@inheritdoc}
 */
class UserContextListener implements EventSubscriberInterface
{
    /**
     * The options configured in the constructor argument or default values.
     *
     * @var array
     */
    private $options;

    /**
     * Generated user hash.
     *
     * @var string
     */
    private $userHash;

    /**
     * When creating this listener, you can configure a number of options.
     *
     * - anonymous_hash:          Hash used for anonymous user. Hash lookup skipped for anonymous if this is set.
     * - user_hash_accept_header: Accept header value to be used to request the user hash to the
     *                            backend application. Must match the setup of the backend application.
     * - user_hash_header:        Name of the header the user context hash will be stored into. Must
     *                            match the setup for the Vary header in the backend application.
     * - user_hash_uri:           Target URI used in the request for user context hash generation.
     * - user_hash_method:        HTTP Method used with the hash lookup request for user context hash generation.
     * - user_identifier_headers: List of request headers that authenticate a non-anonymous request.
     * - session_name_prefix:     Prefix for session cookies. Must match your PHP session configuration.
     *                            To completely ignore the cookies header and consider requests with cookies
     *                            anonymous, pass false for this option.
     *
     * @param array $options Options to overwrite the default options
     *
     * @throws \InvalidArgumentException if unknown keys are found in $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'anonymous_hash' => null,
            'user_hash_accept_header' => 'application/vnd.fos.user-context-hash',
            'user_hash_header' => 'X-User-Context-Hash',
            'user_hash_uri' => '/_fos_user_context_hash',
            'user_hash_method' => 'GET',
            'user_identifier_headers' => ['Authorization', 'HTTP_AUTHORIZATION', 'PHP_AUTH_USER'],
            'session_name_prefix' => 'PHPSESSID',
        ]);

        $resolver->setAllowedTypes('anonymous_hash', ['null', 'string']);
        $resolver->setAllowedTypes('user_hash_accept_header', ['string']);
        $resolver->setAllowedTypes('user_hash_header', ['string']);
        $resolver->setAllowedTypes('user_hash_uri', ['string']);
        $resolver->setAllowedTypes('user_hash_method', ['string']);
        // actually string[] but that is not supported by symfony < 3.4
        $resolver->setAllowedTypes('user_identifier_headers', ['array']);
        $resolver->setAllowedTypes('session_name_prefix', ['string', 'boolean']);

        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
        ];
    }

    /**
     * Look at the request before it is handled by the kernel.
     *
     * Adds the user hash header to the request.
     *
     * Checks if an external request tries tampering with the use context hash mechanism
     * to prevent attacks.
     */
    public function preHandle(CacheEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isInternalRequest($request)) {
            // Prevent tampering attacks on the hash mechanism
            if ($request->headers->get('accept') === $this->options['user_hash_accept_header']
                || null !== $request->headers->get($this->options['user_hash_header'])
            ) {
                $event->setResponse(new Response('Bad Request', 400));

                return;
            }

            // We must not call $request->isMethodCacheable() here, because this will mess up with later configuration of the `http_method_override` that is set onto the request by the Symfony FrameworkBundle.
            // See http://symfony.com/doc/current/reference/configuration/framework.html#configuration-framework-http-method-override
            if (in_array($request->getRealMethod(), ['GET', 'HEAD']) && $hash = $this->getUserHash($event->getKernel(), $request)) {
                $request->headers->set($this->options['user_hash_header'], $hash);
            }
        }

        // let the kernel handle this request.
    }

    /**
     * Remove unneeded things from the request for user hash generation.
     *
     * Cleans cookies header to only keep the session identifier cookie, so the hash lookup request
     * can be cached per session.
     */
    protected function cleanupHashLookupRequest(Request $hashLookupRequest, Request $originalRequest): void
    {
        $sessionIds = [];
        if (!$this->options['session_name_prefix']) {
            $hashLookupRequest->headers->remove('Cookie');

            return;
        }

        foreach ($originalRequest->cookies as $name => $value) {
            if ($this->isSessionName($name)) {
                $sessionIds[$name] = $value;
                $hashLookupRequest->cookies->set($name, $value);
            }
        }

        if (count($sessionIds) > 0) {
            $hashLookupRequest->headers->set('Cookie', http_build_query($sessionIds, '', '; '));
        }
    }

    /**
     * Checks if passed request object is to be considered internal (e.g. for user hash lookup).
     */
    private function isInternalRequest(Request $request): bool
    {
        return true === $request->attributes->get('internalRequest', false);
    }

    /**
     * Returns the user context hash for $request.
     */
    private function getUserHash(HttpKernelInterface $kernel, Request $request): ?string
    {
        if (isset($this->userHash)) {
            return $this->userHash;
        }

        if ($this->options['anonymous_hash'] && $this->isAnonymous($request)) {
            return $this->userHash = $this->options['anonymous_hash'];
        }

        // Hash lookup request to let the backend generate the user hash
        $hashLookupRequest = $this->generateHashLookupRequest($request);
        $resp = $kernel->handle($hashLookupRequest);
        // Store the user hash in memory for sub-requests (processed in the same thread).
        $this->userHash = $resp->headers->get($this->options['user_hash_header']);

        return $this->userHash;
    }

    /**
     * Checks if current request is considered anonymous.
     */
    private function isAnonymous(Request $request): bool
    {
        $anonymousRequestMatcher = new AnonymousRequestMatcher([
            'user_identifier_headers' => $this->options['user_identifier_headers'],
            'session_name_prefix' => $this->options['session_name_prefix'],
        ]);

        return $anonymousRequestMatcher->matches($request);
    }

    /**
     * Checks if passed string can be considered as a session name, such as would be used in cookies.
     *
     * @param string $name
     */
    private function isSessionName($name): bool
    {
        return 0 === strpos($name, $this->options['session_name_prefix']);
    }

    /**
     * Generates the request object that will be forwarded to get the user context hash.
     *
     * @return Request the request that will return the user context hash value
     */
    private function generateHashLookupRequest(Request $request): Request
    {
        $hashLookupRequest = Request::create($this->options['user_hash_uri'], $this->options['user_hash_method'], [], [], [], $request->server->all());
        $hashLookupRequest->attributes->set('internalRequest', true);
        $hashLookupRequest->headers->set('Accept', $this->options['user_hash_accept_header']);
        $this->cleanupHashLookupRequest($hashLookupRequest, $request);

        return $hashLookupRequest;
    }
}
