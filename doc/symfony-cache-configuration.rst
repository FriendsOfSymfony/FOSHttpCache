.. _symfony httpcache configuration:

Symfony HttpCache Configuration
-------------------------------

The ``symfony/http-kernel`` component provides a reverse proxy implemented
completely in PHP, called `HttpCache`_. While it is certainly less efficient
than using Varnish or NGINX, it can still provide considerable performance
gains over an installation that is not cached at all. It can be useful for
running an application on shared hosting for instance.

You can use features of this library with the help of the
``EventDispatchingHttpCache`` provided here. The basic concept is to use event
subscribers on the HttpCache class.

.. warning::

    If you are using the full stack Symfony framework, have a look at the
    HttpCache provided by the FOSHttpCacheBundle_ instead.

.. note::

    Symfony HttpCache does not currently provide support for banning.

Extending the Correct HttpCache Class
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of extending ``Symfony\Component\HttpKernel\HttpCache\HttpCache``, your
``AppCache`` should extend ``FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache``.

.. tip::

    If your class already needs to extend a different class, simply copy the
    event handling code from the EventDispatchingHttpCache into your
    ``AppCache`` class and make it implement ``CacheInvalidationInterface``.
    The drawback is that you need to manually check whether you need to adjust
    your ``AppCache`` each time you update the FOSHttpCache library.

Now that you have an event dispatching kernel, you can make it register the
subscribers you need. While you could do that from your bootstrap code, this is
not the recommended way. You would need to adjust every place you instantiate
the cache. Instead, overwrite the constructor of AppCache and register the
subscribers there. A simple cache will look like this::

    use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
    use FOS\HttpCache\SymfonyCache\UserContextSubscriber;

    class AppCache extends EventDispatchingHttpCache
    {
        /**
         * Overwrite constructor to register event subscribers for FOSHttpCache.
         */
        public function __construct(HttpKernelInterface $kernel, $cacheDir = null)
        {
            parent::__construct($kernel, $cacheDir);

            $this->addSubscriber(new UserContextSubscriber());
            $this->addSubscriber(new PurgeSubscriber());
            $this->addSubscriber(new RefreshSubscriber());
        }
    }

Purge
~~~~~

To support :ref:`cache purging <proxy-client purge>`, register the
``PurgeSubscriber``. If the default settings are right for you, you don't
need to do anything more.

Purging is only allowed from the same machine by default. To purge data from
other hosts, provide the IPs of the machines allowed to purge, or provide a
RequestMatcher that checks for an Authorization header or similar. *Only set
one of purge_client_ips or purge_client_matcher*.

* **purge_client_ips**: String with IP or array of IPs that are allowed to
  purge the cache.

  **default**: ``127.0.0.1``

* **purge_client_matcher**: RequestMatcher that only matches requests that are
  allowed to purge.

  **default**: ``null``

* **purge_method**: HTTP Method used with purge requests.

  **default**: ``PURGE``

Refresh
~~~~~~~

To support :ref:`cache refresh <proxy-client refresh>`, register the
``RefreshSubscriber``. You can pass the constructor an option to specify
what clients are allowed to refresh cache entries. Refreshing is only allowed
from the same machine by default. To refresh from other hosts, provide the
IPs of the machines allowed to refresh, or provide a RequestMatcher that
checks for an Authorization header or similar. *Only set one of
refresh_client_ips or refresh_client_matcher*.

The refresh subscriber needs to access the ``HttpCache::fetch`` method which
is protected on the base HttpCache class. The ``EventDispatchingHttpCache``
exposes the method as public, but if you implement your own kernel, you need
to overwrite the method to make it public.

* **refresh_client_ips**: String with IP or array of IPs that are allowed to
  refresh the cache.

  **default**: ``127.0.0.1``

* **refresh_client_matcher**: RequestMatcher that only matches requests that are
  allowed to refresh.

  **default**: ``null``

.. _symfony-cache user context:

User Context
~~~~~~~~~~~~

To support :doc:`user context hashing <user-context>` you need to register the
``UserContextSubscriber``. The user context is then automatically recognized
based on session cookies or authorization headers. If the default settings are
right for you, you don't need to do anything more. You can customize a number of
options through the constructor:

* **anonymous_hash**: Hash used for anonymous user. This is a performance
  optimization to not do a backend request for users that are not logged in.

* **user_hash_accept_header**: Accept header value to be used to request the
  user hash to the backend application. Must match the setup of the backend
  application.

  **default**: ``application/vnd.fos.user-context-hash``

* **user_hash_header**: Name of the header the user context hash will be stored
  into. Must match the setup for the Vary header in the backend application.

  **default**: ``X-User-Context-Hash``

* **user_hash_uri**: Target URI used in the request for user context hash
  generation.

  **default**: ``/_fos_user_context_hash``

* **user_hash_method**: HTTP Method used with the hash lookup request for user
  context hash generation.

  **default**: ``GET``

* **session_name_prefix**: Prefix for session cookies. Must match your PHP session configuration.

  **default**: ``PHPSESSID``

.. warning::

    If you have a customized session name, it is **very important** that this
    constant matches it.
    Session IDs are indeed used as keys to cache the generated use context hash.

    Wrong session name will lead to unexpected results such as having the same
    user context hash for every users, or not having it cached at all, which
    hurts performance.

.. note::

    To use authorization headers for user context, you might have to add some server
    configuration to make these headers available to PHP.

    With Apache, you can do this for example in a ``.htaccess`` file::

        RewriteEngine On
        RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

Cleaning the Cookie Header
^^^^^^^^^^^^^^^^^^^^^^^^^^

By default, the UserContextSubscriber only sets the session cookie (according to
the ``session_name_prefix`` option) in the requests to the backend. If you need
a different behavior, overwrite ``UserContextSubscriber::cleanupHashLookupRequest``
with your own logic.

.. _HttpCache: http://symfony.com/doc/current/book/http_cache.html#symfony-reverse-proxy
