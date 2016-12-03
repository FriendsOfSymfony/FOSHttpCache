.. _symfony httpcache configuration:

Symfony HttpCache Configuration
-------------------------------

Symfony’s `HttpKernel`_ component provides a reverse proxy implemented
completely in PHP, called `HttpCache`_. While it is certainly less efficient
than using Varnish or NGINX, it can still provide considerable performance
gains over an installation that is not cached at all. It can be useful for
running an application on shared hosting for instance.

You can use features of this library with the help of event listeners that act
on events of the ``HttpCache``. The Symfony ``HttpCache`` does not have an
event system, for this you need to use the trait ``EventDispatchingHttpCache``
provided by this library. The event listeners handle the requests from the
:doc:`cache invalidator <cache-invalidator>`.

.. note::

    Symfony ``HttpCache`` does not currently provide support for banning.

Using the trait
~~~~~~~~~~~~~~~

.. note::

    The trait is available since version 2.0.0. Version 1.* of this library
    instead provided a base ``HttpCache`` class to extend.

Your ``AppCache`` needs to implement ``CacheInvalidation`` and use the
trait ``FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache``::

    use FOS\HttpCache\SymfonyCache\CacheInvalidation;
    use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\HttpCache\HttpCache;

    class AppCache extends HttpCache implements CacheInvalidation
    {
        use EventDispatchingHttpCache;

        /**
         * Made public to allow event listeners to do refresh operations.
         *
         * {@inheritDoc}
         */
        public function fetch(Request $request, $catch = false)
        {
            return parent::fetch($request, $catch);
        }
    }

The trait adds the ``addSubscriber`` and ``addListener`` methods as defined in
the ``EventDispatcherInterface`` to your cache kernel. In addition, it triggers
events before and/or after kernel methods to let the listeners interact. If you
need to overwrite core ``HttpCache`` functionality in your kernel, you can
provide your own event listeners. If you need to implement functionality
directly on the methods, be careful to always call the trait methods rather
than going directly to the parent, or events will not be triggered anymore. You
might also need to copy a method from the trait and add your own logic between
the events to not be too early or too late for the event.

When starting to extend your ``AppCache``, it is recommended to use the
``EventDispatchingHttpCacheTestCase`` to run tests with your kernel to be sure
all events are triggered as expected.

.. note::

    If you use ``HttpKernel::loadClassCache`` from the console, you will need
    to add ``class_exists('FOS\\HttpCache\\SymfonyCache\\CacheEvent');`` right
    after the inclusion of ``bootstrap.php.cache`` in ``app/console``. For web
    requests, this is done automatically by the trait. If you miss to do so,
    you will get the following error:

    .. code-block:: bash

        Fatal error: Cannot redeclare class Symfony\Component\EventDispatcher\Event in app/cache/dev/classes.php on line ...

Cache event listeners
~~~~~~~~~~~~~~~~~~~~~

Now that you have an event dispatching kernel, you can make it register the
listeners you need. While you could do that from your bootstrap code, this is
not the recommended way. You would need to adjust every place you instantiate
the cache. Instead, overwrite the constructor of your ``AppCache`` and register
the listeners you need there::

    use FOS\HttpCache\SymfonyCache\DebugListener();
    use FOS\HttpCache\SymfonyCache\CustomTtlListener();
    use FOS\HttpCache\SymfonyCache\PurgeListener;
    use FOS\HttpCache\SymfonyCache\RefreshListener;
    use FOS\HttpCache\SymfonyCache\UserContextListener;

    // ...

    /**
     * Overwrite constructor to register event listeners for FOSHttpCache.
     */
    public function __construct(
        HttpKernelInterface $kernel,
        StoreInterface $store,
        SurrogateInterface $surrogate = null,
        array $options = []
    ) {
        parent::__construct($kernel, $store, $surrogate, $options);

        $this->addSubscriber(new CustomTtlListener());
        $this->addSubscriber(new PurgeListener());
        $this->addSubscriber(new RefreshListener());
        $this->addSubscriber(new UserContextListener());
        if (isset($options['debug']) && $options['debug']) {
            $this->addSubscriber(new DebugListener());
        }
    }

The event listeners can be tweaked by passing options to the constructor. The
Symfony configuration system does not work here because things in the cache
happen before the configuration is loaded.

Purge
~~~~~

To support :ref:`cache invalidation <cache invalidate>`, register the
``PurgeListener``. If the default settings are right for you, you don't
need to do anything more.

Purging is only allowed from the same machine by default. To purge data from
other hosts, provide the IPs of the machines allowed to purge, or provide a
RequestMatcher that checks for an Authorization header or similar. *Only set
one of ``client_ips`` or ``client_matcher``*.

* **client_ips**: String with IP or array of IPs that are allowed to
  purge the cache.

  **default**: ``127.0.0.1``

* **client_matcher**: RequestMatcherInterface that only matches requests that are
  allowed to purge.

  **default**: ``null``

* **purge_method**: HTTP Method used with purge requests.

  **default**: ``PURGE``

Refresh
~~~~~~~

To support :ref:`cache refresh <cache refresh>`, register the
``RefreshListener``. You can pass the constructor an option to specify
what clients are allowed to refresh cache entries. Refreshing is only allowed
from the same machine by default. To refresh from other hosts, provide the
IPs of the machines allowed to refresh, or provide a RequestMatcher that
checks for an Authorization header or similar. *Only set one of
``client_ips`` or ``client_matcher``*.

The refresh listener needs to access the ``HttpCache::fetch`` method which
is protected on the base HttpCache class. The ``EventDispatchingHttpCache``
exposes the method as public, but if you implement your own kernel, you need
to overwrite the method to make it public.

* **client_ips**: String with IP or array of IPs that are allowed to
  refresh the cache.

  **default**: ``127.0.0.1``

* **client_matcher**: RequestMatcher that only matches requests that are
  allowed to refresh.

  **default**: ``null``

.. _symfony-cache user context:

User Context
~~~~~~~~~~~~

To support :doc:`user context hashing <user-context>` you need to register the
``UserContextListener``. The user context is then automatically recognized
based on session cookies or authorization headers. If the default settings are
right for you, you don't need to do anything more. You can customize a number of
options through the constructor:

* **anonymous_hash**: Hard-coded hash to use for anonymous users. This is a
  performance optimization to not do a backend request for users that are not
  logged in. If you specify a non-empty value for this field, that is used as
  context hash header instead of doing a hash lookup for anonymous users.

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

By default, the UserContextListener only sets the session cookie (according to
the ``session_name_prefix`` option) in the requests to the backend. If you need
a different behavior, overwrite ``UserContextListener::cleanupHashLookupRequest``
with your own logic.

.. _symfonycache_customttl:

Custom TTL
~~~~~~~~~~

.. include:: includes/custom-ttl.rst

The ``CustomTtlListener`` looks at a specific header to determine the TTL,
preferring that over ``s-maxage``. The default header is ``X-Reverse-Proxy-TTL``
but you can customize that in the listener constructor::

    new CustomTtlListener('My-TTL-Header');

The custom header is removed before sending the response to the client.

.. _symfony-cache x-debugging:

Debugging
~~~~~~~~~

For the ``assertHit`` and ``assertMiss`` assertions to work, you need to add
debug information in your AppCache. When running the tests, create the cache
kernel with the option ``'debug' => true`` and add the ``DebugListener``.

The ``UNDETERMINED`` state should never happen. If it does, it means that
something went really wrong in the kernel. Have a look at ``X-Symfony-Cache``
and at the HTML body of the response.

.. _HttpCache: http://symfony.com/doc/current/book/http_cache.html#symfony-reverse-proxy
.. _HttpKernel: http://symfony.com/doc/current/components/http_kernel.html
