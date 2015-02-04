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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * User context handler for the symfony built-in HttpCache.
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 *
 * {@inheritdoc}
 */
class UserContextSubscriber implements EventSubscriberInterface
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
     * When creating this subscriber, you can configure a number of options.
     *
     * - anonymous_hash:          Hash used for anonymous user.
     * - user_hash_accept_header: Accept header value to be used to request the user hash to the
     *                            backend application. Must match the setup of the backend application.
     * - user_hash_header:        Name of the header the user context hash will be stored into. Must
     *                            match the setup for the Vary header in the backend application.
     * - user_hash_uri:           Target URI used in the request for user context hash generation.
     * - user_hash_method:        HTTP Method used with the hash lookup request for user context hash generation.
     * - session_name_prefix:     Prefix for session cookies. Must match your PHP session configuration.
     *
     * @param array $options Options to overwrite the default options
     *
     * @throws \InvalidArgumentException if unknown keys are found in $options
     */
    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'anonymous_hash' => '38015b703d82206ebc01d17a39c727e5',
            'user_hash_accept_header' => 'application/vnd.fos.user-context-hash',
            'user_hash_header' => 'X-User-Context-Hash',
            'user_hash_uri' => '/_fos_user_context_hash',
            'user_hash_method' => 'GET',
            'session_name_prefix' => 'PHPSESSID',
        ));

        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_HANDLE => 'preHandle',
        );
    }

    /**
     * Look at the request before it is handled by the kernel.
     *
     * Adds the user hash header to the request.
     *
     * Checks if an external request tries tampering with the use context hash mechanism
     * to prevent attacks.
     *
     * @param CacheEvent $event
     */
    public function preHandle(CacheEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isInternalRequest($request)) {
            // Prevent tampering attacks on the hash mechanism
            if ($request->headers->get('accept') === $this->options['user_hash_accept_header']
                || $request->headers->get($this->options['user_hash_header']) !== null
            ) {
                $event->setResponse(new Response('', 400));

                return;
            }

            if ($request->isMethodSafe()) {
                $request->headers->set($this->options['user_hash_header'], $this->getUserHash($event->getKernel(), $request));
            }
        }

        // let the kernel handle this request.
    }

    /**
     * Remove unneeded things from the request for user hash generation.
     *
     * Cleans cookies header to only keep the session identifier cookie, so the hash lookup request
     * can be cached per session.
     *
     * @param Request $hashLookupRequest
     * @param Request $originalRequest
     */
    protected function cleanupHashLookupRequest(Request $hashLookupRequest, Request $originalRequest)
    {
        $sessionIds = array();
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
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isInternalRequest(Request $request)
    {
        return $request->attributes->get('internalRequest', false) === true;
    }

    /**
     * Returns the user context hash for $request.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getUserHash(HttpKernelInterface $kernel, Request $request)
    {
        if (isset($this->userHash)) {
            return $this->userHash;
        }

        if ($this->isAnonymous($request)) {
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
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isAnonymous(Request $request)
    {
        // You might have to enable rewriting of the Authorization header in your server config or .htaccess:
        // RewriteEngine On
        // RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
        if ($request->server->has('AUTHORIZATION') ||
            $request->server->has('HTTP_AUTHORIZATION') ||
            $request->server->has('PHP_AUTH_USER')
        ) {
            return false;
        }

        foreach ($request->cookies as $name => $value) {
            if ($this->isSessionName($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if passed string can be considered as a session name, such as would be used in cookies.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isSessionName($name)
    {
        return strpos($name, $this->options['session_name_prefix']) === 0;
    }

    /**
     * Generates the request object that will be forwarded to get the user context hash.
     *
     * @param Request $request
     *
     * @return Request The request that will return the user context hash value.
     */
    private function generateHashLookupRequest(Request $request)
    {
        $hashLookupRequest = Request::create($this->options['user_hash_uri'], $this->options['user_hash_method'], array(), array(), array(), $request->server->all());
        $hashLookupRequest->attributes->set('internalRequest', true);
        $hashLookupRequest->headers->set('Accept', $this->options['user_hash_accept_header']);
        $this->cleanupHashLookupRequest($hashLookupRequest, $request);

        return $hashLookupRequest;
    }
}
