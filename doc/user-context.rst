Cache on User Context
=====================

In applications that differentiate between user types, served content can be
different per user type. For instance, on one and the same URL a guest sees a
‘Log in’ message; an editor sees an ‘Edit’ button and the administrator a link
to the admin backend.

The FOSHttpCache library includes a solution to vary cached responses based on
user context (whether the user is authenticated, his access rights, or other
information).

Overview
--------

Caching on user context works as follows:

1. A :term:`client` requests ``/foo.php`` (the *original request*).
2. The :term:`caching proxy`  receives the request. It sends a request
   (the *hash request*) with a special accept header
   (``application/vnd.fos.user-context-hash``) to a specific URL,
   e.g., ``/auth.php``.
3. The :term:`application` receives the hash request. The application knows the
   client’s user context (roles, permissions, etc.) and generates a hash based
   on that information. The application then returns a response containing that
   hash in a custom header (``X-User-Context-Hash``) and with ``Content-Type``
   ``application/vnd.fos.user-context-hash``.
4. The caching proxy receives the hash response, copies the hash header to the
   client’s original request for ``/foo.php`` and restarts that request.
5. If the response to this request should differ per user context, the
   application specifies so by setting a ``Vary: X-User-Context-Hash`` header.
   The appropriate user role dependent representation of ``/foo.php`` will
   then be returned to the client.

Proxy Client Configuration
--------------------------

Currently, user context caching is only supported by Varnish. See the
:ref:`Varnish Configuration <varnish user context>` on how to prepare Varnish properly.

Calculating the User Context Hash
---------------------------------

The user context hash calculation (step 3 above) is managed by the
`HashGenerator <../../../src/UserContext/HashGenerator.php>`_. Because the calculation
itself will be different per application, you need to implement at least one
ContextProvider and register that with the HashGenerator.

.. code-block:: php

    use FOS\HttpCache\UserContext\HashGenerator;

    $hashGenerator = new HashGenerator();
    $hashGenerator->registerProvider(IsAuthenticatedProvider());
    $hashGenerator->registerProvider(RoleProvider());

Once all providers are registered you can call ``generateHash()`` to get the hash
for the current user context.

Context Providers
-----------------

Each provider is passed the `UserContext <../../../src/UserContext/UserContext.php>`_
and updates that with parameters which influence the varied response.

A provider that looks at whether the user is authenticated could look like this:

.. code-block:: php

    use FOS\HttpCache\UserContext\ContextProviderInterface;
    use FOS\HttpCache\UserContext\UserContext;

    class IsAuthenticatedProvider implements ContextProviderInterface
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

Returning the User Context Hash
-------------------------------

It is up to you to return the user context hash in response to the hash request
(``/auth.php`` in step 3 above):

.. code-block:: php

    $hash = $hashGenerator->generateHash();

    if ('application/vnd.fos.user-context-hash' == strtolower($_SERVER['HTTP_ACCEPT'])) {
        header(sprintf('X-User-Context-Hash: %s', $hash));
        header('Content-Type: application/vnd.fos.user-context-hash');
        exit;
    }

    // 406 Not acceptable in case of an incorrect accept header
    header('HTTP/1.1 406');

If you use Symfony2, the FOSHttpCacheBundle_ will set the correct response
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

The Original Request
--------------------

After following the steps above, the following code renders a homepage
differently depending on whether the user is logged in or not:

.. code-block:: php

    // /index.php file
    header('Cache-Control: max-age=3600');
    header('Vary: X-User-Context-Hash');

    $authenticationService = new AuthenticationService();

    if ($authenticationService->isAuthenticated()) {
        echo "You are authenticated";
    } else {
        echo "You are anonymous";
    }
