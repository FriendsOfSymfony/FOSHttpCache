.. _user-context:

Cache on User Context
=====================

Some applications differentiate the content between types of users. For
instance, on one and the same URL a guest sees a ‘Log in’ message; an editor
sees an ‘Edit’ button and the administrator a link to the admin backend.

The FOSHttpCache library includes a solution to cache responses per user
context (whether the user is authenticated, groups the user is in, or other
information), rather than individually.

If every user has their own hash, you probably don't want to cache at all. Or
if you found out its worth it, vary on the credentials and don't use the
context hash mechanism.

.. caution::

    Whenever you share caches, make sure to **not output any individual content**
    like the user name. If you have individual parts of a page, you can load
    those parts over AJAX requests or look into ESI_ and make the ESI sub
    response vary on the cookie or completely non-cached. Both approaches
    integrate with the concepts presented in this chapter.

    You do **not want intermediary proxies to cache responses** that depend on the
    context. If the client will not see a difference when his context changes
    (e.g. is  removed from or added to groups on server side), you also do not
    want the clients to cache pages. Because ``VARY`` is used for the control
    of the proxy server, it is not available to control clients. Often, the
    best solution is to disable intermediary caches by setting the cache control
    header ``s-maxage`` to 0 and using the *custom TTL* mechanism (see the
    documentation for :ref:`Varnish <varnish_customttl>` or the
    :ref:`Symfony HttpCache <symfonycache_customttl>`). If you want to use the
    ``private`` cache control instruction instead, you need to adjust your
    proxy server configuration to cache content with a ``private`` instruction.

Overview
--------

Caching on user context works as follows:

1. A :term:`client` requests ``/foo.php`` (the *original request*).
2. The :term:`proxy server`  receives the request. It sends a request
   (the *hash request*) with a special accept header
   (``application/vnd.fos.user-context-hash``) to a specific URL,
   e.g., ``/_fos_user_context_hash``.
3. The :term:`application` receives the hash request. The application knows the
   client’s user context (roles, permissions, etc.) and generates a hash based
   on that information. The application then returns a response containing that
   hash in a custom header (``X-User-Context-Hash``) and with ``Content-Type``
   ``application/vnd.fos.user-context-hash``.
4. The proxy server receives the hash response, copies the hash header to the
   client’s original request for ``/foo.php`` and restarts that request.
5. If the response to this request should differ per user context, the
   application specifies so by setting a ``Vary: X-User-Context-Hash`` header.
   The appropriate user role dependent representation of ``/foo.php`` will
   then be returned to the client.

After the first time, the hash lookup response for this client can be cached by
the proxy server, moving step 2-4 into the cache. After the page is in cache,
subsequent requests from other clients that received the same hash can be served
from the cache as well.

.. note::

    When using the Symfony HttpCache, you can configure the UserContextListener
    with a fixed hash to use in case there are neither cookie nor
    authentication information. If you configure the hash to use, the hash
    lookup is skipped in the case of anonymous requests.

    If your application starts sessions for anonymous users, you will need one
    hash lookup request for each of those users. Your application can return
    the same hash for authenticated users with no special privileges as for
    anonymous users with a session cookie.

.. warning::


    With a session cookie, the hash lookup can not be avoided, because the
    proxy server can not know which session cookies indicate a logged in user
    and which an anonymous session. When using the Symfony HttpCache with the
    user context, you will have a problem because all variants are saved into
    the same file on disk. The problem is increased by HttpCache not evicting
    old variants. The cache file that holds the hash lookup request will just
    keep growing.

Proxy Client Configuration
--------------------------

Currently, user context caching is only supported by Varnish and by the Symfony
HttpCache. See the :ref:`Varnish Configuration <varnish user context>` or
:ref:`Symfony HttpCache Configuration <symfony-cache user context>`.

User Context Hash from Your Application
---------------------------------------

It is your application's responsibility to determine the hash for a user. Only
your application can know what is relevant for the hash. You can use the path
or the accept header to detect that a hash was requested.

.. warning::

    Treat the hash lookup path like the login path so that anonymous users also
    can get a hash. That means that your cache can access the hash lookup even
    with no user provided credential and that the hash lookup never redirects
    to a login page.

Calculating the User Context Hash
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The user context hash calculation (step 3 above) is managed by a HashGenerator.
Because the calculation itself will be different per application, you need to
implement at least one ContextProvider and register that with the DefaultHashGenerator::

    use FOS\HttpCache\UserContext\DefaultHashGenerator;

    $hashGenerator = new DefaultHashGenerator([
        new IsAuthenticatedProvider(),
        new RoleProvider(),
    ]);

Once all providers are registered, call ``generateHash()`` to get the hash for
the current user context.

.. note::

    If you need custom logic in the hash generator you can create your own class
    implementing the HashGenerator interface.

Context Providers
~~~~~~~~~~~~~~~~~

Each provider is passed the :source:`UserContext <src/UserContext/UserContext.php>`
and updates that with parameters which influence the varied response.

A provider that looks at whether the user is authenticated could look like this::

    use FOS\HttpCache\UserContext\ContextProvider;
    use FOS\HttpCache\UserContext\UserContext;

    class IsAuthenticatedProvider implements ContextProvider
    {
        protected $userService;

        public function __construct(YourUserService $userService)
        {
            $this->userService = $userService;
        }

        public function updateUserContext(UserContext $userContext)
        {
            $userContext->addParameter('authenticated', $this->userService->isAuthenticated());
        }
    }

.. _return context hash:

Returning the User Context Hash
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

It is up to you to return the user context hash in response to the hash request
(``/_fos_user_context_hash`` in step 3 above)::

    // <web-root>/_fos_user_context_hash/index.php

    $hash = $hashGenerator->generateHash();

    if ('application/vnd.fos.user-context-hash' == strtolower($_SERVER['HTTP_ACCEPT'])) {
        header(sprintf('X-User-Context-Hash: %s', $hash));
        header('Content-Type: application/vnd.fos.user-context-hash');
        exit;
    }

    // 406 Not acceptable in case of an incorrect accept header
    header('HTTP/1.1 406');

If you use Symfony, the FOSHttpCacheBundle_ will set the correct response
headers for you.

Caching the Hash Response
~~~~~~~~~~~~~~~~~~~~~~~~~

To optimize user context hashing performance, you should cache the hash
response. By varying on the Cookie and Authorization header, the
application will return the correct hash for each user. This way, subsequent
hash requests (step 3 above) will be served from cache instead of requiring a
roundtrip to the application.

.. literalinclude:: ../tests/Functional/Fixtures/web/user_context_hash_cache.php
    :language: php
    :start-after: header
    :emphasize-lines: 7-8

Here we say that the hash is valid for one hour. Keep in mind, however, that
you need to invalidate the hash response when the parameters that determine
the context change for a user, for instance, when the user logs in or out, or
is granted extra permissions by an administrator.

.. note::

    If you base the user hash on the Cookie header, you should clean up that header
    to make the hash request properly cacheable: :ref:`Varnish <varnish_cookie_header>`,
    :ref:`Symfony HttpCache <symfony_cookie_header>`.

The Original Request
--------------------

After following the steps above, the following code renders a homepage
differently depending on whether the user is logged in or not, using the
*credentials of the particular user*::

    // /index.php file
    header('Cache-Control: max-age=3600');
    header('Vary: X-User-Context-Hash');

    $authenticationService = new AuthenticationService();

    if ($authenticationService->isAuthenticated()) {
        echo "You are authenticated";
    } else {
        echo "You are anonymous";
    }

.. _paywall_usage:

Alternative for Paywalls: Authorization Request
-----------------------------------------------

If you can't efficiently determine a general user hash for the whole
application (e.g. you have a paywall_ where individual users are limited to
individual content), you can follow a slightly different approach:

* Instead of doing a hash lookup request to a specific authentication URL, you
  keep the request URL unchanged, but send a HEAD request with a specific
  ``Accept`` header.
* In your application, you intercept such requests after the access decision
  has taken place but before expensive operations like loading the actual data
  have taken place and return early with a 200 or 403 status.
* If the status was 200, you restart the request in Varnish, and cache the
  response even though a ``Cookie`` or ``Authorization`` header is present, so
  that further requests on the same URL by other authorized users can be
  served from cache. On status 403 you return an error page or redirect to the
  URL where the content can be bought.

.. _ESI: http://en.wikipedia.org/wiki/Edge_Side_Includes
.. _paywall: http://en.wikipedia.org/wiki/Paywall
