Proxy Client Setup
==================

This library ships with clients for the Varnish and NGINX caching servers and
the Symfony built-in HTTP cache.

A Multiplexer client that forwards calls to multiple proxy clients is
available, mainly for transition scenarios of your applications. A Noop client
that implements the interfaces but does nothing at all is provided for local
development and testing purposes.

The recommended usage is to have your application interact with the
:doc:`cache invalidator <cache-invalidator>` which you set up with the proxy
client suitable for the proxy server you use.

Supported invalidation methods
------------------------------

Not all clients support all :ref:`invalidation methods <invalidation methods>`.
This table provides of methods supported by each proxy client:

============= ======= ======= ======= ======= =======
Client        Purge   Refresh Ban     Tagging Clear
============= ======= ======= ======= ======= =======
Varnish       ✓       ✓       ✓       ✓
NGINX         ✓       ✓
Symfony Cache ✓       ✓               ✓ (1)   ✓ (1)
Noop          ✓       ✓       ✓       ✓
Multiplexer   ✓       ✓       ✓       ✓
============= ======= ======= ======= ======= =======

(1): Only when using `Toflar Psr6Store`_.


Of course, you can also implement your own client for other needs. Have a look
at the interfaces in namespace ``FOS\HttpCache\ProxyClient\Invalidation``.

.. _client setup:

Setup
-----

Most proxy clients use the ``HttpDispatcher`` to send requests to the proxy
server. The ``HttpDispatcher`` is built on top of the HTTPlug_ abstraction to
be independent of specific HTTP client implementations.

.. _HTTP client configuration:

Basic HTTP setup with HttpDispatcher
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The dispatcher needs to know the IP addresses or hostnames of your proxy
servers. If your proxy servers do not run on the default port (80 for HTTP,
443 for HTTPS), you need to specify the port with the server name. Make sure to
provide the direct access to the web server without any other proxies that
might block invalidation requests.

The server IPs are sufficient for invalidating absolute URLs. If you want to
use relative paths in invalidation requests, supply the hostname and possibly
a base path to your website with the ``$baseUri`` parameter::

    use FOS\HttpCache\ProxyClient\HttpDispatcher;

    $servers = ['10.0.0.1', '10.0.0.2:6081']; // Port 80 assumed for 10.0.0.1
    $baseUri = 'my-cool-app.com';
    $httpDispatcher = new HttpDispatcher($servers, $baseUri);

If your web application is accessed on a port other than the default port, make
sure to include that port in the base URL::

    $baseUri = 'my-cool-app.com:8080';

You can additionally specify the HTTP client and URI factory that should be
used. If you specify a custom HTTP client, you need to configure the client to
convert HTTP error status into exceptions. This can either be done in a client
specific way or with the HTTPlug ``PluginClient`` and the ``ErrorPlugin``.
If client and/or URI factory are not specified, the dispatcher uses
`HTTPlug discovery`_ to find available implementations.

Learn more about available HTTP clients `in the HTTPlug documentation`_. To
customize the behavior of the HTTP client, you can use `HTTPlug plugins`_.

.. _varnish client:

Varnish Client
~~~~~~~~~~~~~~

The Varnish client sends HTTP requests with the ``HttpDispatcher``. Create the
dispatcher as explained above and pass it to the Varnish client::

    use FOS\HttpCache\ProxyClient\Varnish;

    $varnish = new Varnish($httpDispatcher);

.. note::

    To make invalidation work, you need to :doc:`configure Varnish <varnish-configuration>` accordingly.

.. _varnish_custom_tags_header:

You can also pass some options to the Varnish client:

* ``tags_header`` (default: ``X-Cache-Tags``): The HTTP header used to specify
  which tags to invalidate when sending invalidation requests to the caching
  proxy. Make sure that your :ref:`Varnish configuration <varnish_tagging>`
  corresponds to the header used here;
* ``tag_mode`` (default: ban): Select whether to invalidate tags using the xkey
  module or with ban requests. Supported modes: ``ban`` and ``purgekeys``.
* ``header_length`` (default: 7500): Control the maximum header length when
  invalidating tags. If there are more tags to invalidate than fit into the
  header, the invalidation request is split into several requests;
* ``default_ban_headers`` (default: []): Map of headers that are set on each
  ban request, merged with the built-in headers.

Additionally, you can specify the request factory used to build the
invalidation HTTP requests. If not specified, auto discovery is used - which
usually is fine.

A full example could look like this::

    $options = [
        'tags_header' => 'X-Custom-Tags-Header',
        'header_length' => 4000,
        'default_ban_headers' => [
            'EXTRA-HEADER' => 'header-value',
        ]
    ];
    $requestFactory = new MyRequestFactory();

    $varnish = new Varnish($httpDispatcher, $options, $requestFactory);

Configuring the Client for xkey Tag Invalidation
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you :ref:`install the varnish modules to use xkey tagging <varnish_tagging>`,
you need to adjust the Varnish client as well::

    use FOS\HttpCache\ProxyClient\Varnish;

    $options = [
        'tag_mode' => 'purgekeys'
    ];

    $varnish = new Varnish($httpDispatcher, $options);

If you do not want to use soft purge (either because your varnish modules
version is too old to support it or because it does not fit your scenario),
additionally set the ``tags_header`` option to ``xkey-purge`` instead of the
default ``xkey-softpurge``.

.. note::

    For xkey to work, the response tags MUST be given in a header named
    ``xkey`` and separated by space rather than the default ``,``. If you use
    the ``ResponseTagger``, set it up with a
    :ref:`custom TagHeaderFormatter <response_tagger_optional_parameters>`.

NGINX Client
~~~~~~~~~~~~

The NGINX client sends HTTP requests with the ``HttpDispatcher``. Create the
dispatcher as explained above and pass it to the NGINX client::

    use FOS\HttpCache\ProxyClient\Nginx;

    $nginx = new Nginx($httpDispatcher);

If you have configured NGINX to support purge requests at a separate location,
call `setPurgeLocation()`::

    use FOS\HttpCache\ProxyClient\Nginx;

    $nginx = new Nginx($servers, $baseUri);
    $nginx->setPurgeLocation('/purge');

.. note::

    To use the client, you need to :doc:`configure NGINX <nginx-configuration>`
    accordingly.

Symfony Client
~~~~~~~~~~~~~~

The Symfony client sends HTTP requests with the ``HttpDispatcher``. Create the
dispatcher as explained above and pass it to the Symfony client::

    use FOS\HttpCache\ProxyClient\Symfony;

    $symfony = new Symfony($httpDispatcher);

.. note::

    To make invalidation work, you need to :doc:`use the EventDispatchingHttpCache <symfony-cache-configuration>`.

.. _proxy client symfony httpcache kernel dispatcher:

KernelDispatcher for Single Server Installations
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The ``HttpDispatcher`` sends real HTTP requests using any instance of
``HttpAsyncClient`` available in your application. If your application runs on
one single server, you can call the cache kernel directly, inside the same PHP
process, instead of sending actual HTTP requests over the network. This makes
your setup easier as you don't need to know the IP of your server and will also
save server resources.

To do this, use the ``KernelDispatcher`` instead of the ``HttpDispatcher``.
This alternate dispatcher expects a ``HttpCacheProvider`` in the constructor to
provide the ``HttpCache``. The cache is implemented with the decorator pattern
and thus the application kernel does not normally know about the cache. This
library provides the ``HttpCacheAware`` trait to simplify making your kernel
capable of providing the cache.

The recommended way to wire things up is to instantiate the cache kernel in the
kernel constructor to guarantee consistent setup over all entry points. Adjust
your kernel like this::

    // src/AppKernel.php

    namespace App;

    use FOS\HttpCache\SymfonyCache\HttpCacheAware;
    use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
    use Symfony\Component\HttpKernel\Kernel;

    class AppKernel extends Kernel implements HttpCacheProvider
    {
        use HttpCacheAware;
        //...

        public function __construct(...)
        {
            // ...
            $this->setHttpCache(new AppCache($this));
        }
    }

And adapt your bootstrapping code to use the cache kernel::

    // public/index.php

    use FOS\HttpCache\ProxyClient\Symfony;
    use FOS\HttpCache\SymfonyCache\KernelDispatcher;

    $kernel = new App\AppKernel();
    $cacheKernel = $kernel->getHttpCache();

    // Create the Symfony proxy client with KernelDispatcher
    // Use $kernel, not $cacheKernel here!
    $kernelDispatcher = new KernelDispatcher($kernel);
    $symfony = new Symfony($kernelDispatcher);

    ...
    $response = $cacheKernel->handle($request);
    ...

Noop Client
~~~~~~~~~~~

The Noop (no operation) client implements the interfaces for invalidation, but
does nothing. It is useful for developing your application or on a testing
environment that does not have a proxy server set up. Rather than making the
cache invalidator optional in your code, you can (based on the environment)
determine whether to inject the real client or the Noop client. The rest of your
application then does not need to worry about the environment.

.. _multiplexer client:

Multiplexer Client
~~~~~~~~~~~~~~~~~~

The ``MultiplexerClient`` allows to send invalidation requests to multiple
proxy clients.

It is useful when multiple caches exist in the environment and they need to be
handled at the same time; the Multiplexer proxy client will forward the cache
invalidation calls to all proxy clients supporting the operation in question::

    use FOS\HttpCache\ProxyClient\MultiplexerClient;
    use FOS\HttpCache\ProxyClient\Nginx;
    use FOS\HttpCache\ProxyClient\Symfony;

    $nginxClient = new Nginx($servers);
    $symfonyClient = new Symfony([...]);
    // Expects an array of ProxyClient in the constructor
    $client = new MultiplexerClient([$nginxClient, $symfonyClient]);

Invalidation calls on ``MultiplexerClient`` will be forwarded to all proxy
clients that support the :ref:`invalidation method <invalidation methods>` and
be ignored if none do. Calling ``getTagsHeaderValue`` and ``getTagsHeaderName``
will throw an ``UnsupportedProxyOperationException`` if none of the proxy
clients support tagging (i.e., implement ``TagCapable``).

.. note::

    Having multiple layers of HTTP caches in place is not a good idea in
    general. The ``MultiplexerClient`` is provided for special situations, for
    example during a transition phase of an application where an old and a new
    system run in parallel.

.. note::

    When using the multiplexer, code relying on ``instanceof`` checks on the
    client and also the ``CacheInvalidator::supports`` method will not work, as
    the ``MultiplexerClient`` implements all interfaces, but the attached
    clients might not. Make sure that none of the code you use relies on such
    checks - or write your own multiplexer that only implements the interfaces
    supported by the clients you use.

Using the Proxy Client
----------------------

The recommended usage of the proxy client is to create an instance of
``CacheInvalidator`` with the correct client for your setup. See
:doc:`cache-invalidator` for more information.

Implementation Notes
--------------------

Each client is an implementation of :source:`ProxyClient <src/ProxyClient/ProxyClient.php>`.
All other interfaces, ``PurgeCapable``, ``RefreshCapable``, ``BanCapable``, ``ClearCapable`` and
``TagCapable``, extend this ``ProxyClient``. So each client implements at least
one of the three :ref:`invalidation methods <invalidation methods>` depending on
the proxy server’s abilities. To interact with a proxy client directly, refer to
the doc comments on the interfaces.

The ``ProxyClient`` has one method: ``flush()``. After collecting
invalidation requests, ``flush()`` needs to be called to actually send the
requests to the proxy server. This is on purpose: this way, we can send
all requests together, reducing the performance impact of sending invalidation
requests.

.. _HTTPlug: http://httplug.io/
.. _HTTPlug discovery: http://php-http.readthedocs.io/en/latest/discovery.html
.. _in the HTTPlug documentation: http://php-http.readthedocs.io/en/latest/clients.html
.. _HTTPlug plugins: http://php-http.readthedocs.io/en/latest/plugins/index.html
.. _message factory and URI factory: http://php-http.readthedocs.io/en/latest/message/message-factory.html
.. _Toflar Psr6Store: https://github.com/Toflar/psr6-symfony-http-cache-store
