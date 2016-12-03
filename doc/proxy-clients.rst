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
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Not all clients support all :ref:`invalidation methods <invalidation methods>`.
This table provides of methods supported by each proxy client:

============= ======= ======= =======
Client        Purge   Refresh Ban
============= ======= ======= =======
Varnish       ✓       ✓       ✓
NGINX         ✓       ✓
Symfony Cache ✓       ✓
Noop          ✓       ✓       ✓
Multiplexer   ✓       ✓       ✓
============= ======= ======= =======

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

* ``tags_header`` (X-Cache-Tags): Allows you to change the HTTP header used for
  tagging. If you change this, make sure to use the correct header name in your
  :doc:`proxy server configuration <proxy-configuration>`;
* ``header_length`` (7500): Control the maximum header size when invalidating
  tags. If there are more tags to invalidate than fit into the header, the
  invalidation request is split into several requests;
* ``default_ban_headers`` Map of headers that are set on each ban request,
  merged with the built-in headers.

Additionally, you can specify the request factory used to build the
invalidation HTTP requests. If not specified, auto discovery is used – which
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

    To use the client, you need to :doc:`configure NGINX <nginx-configuration>` accordingly.

Symfony Client
~~~~~~~~~~~~~~

The Symfony client sends HTTP requests with the ``HttpDispatcher``. Create the
dispatcher as explained above and pass it to the Symfony client::

    use FOS\HttpCache\ProxyClient\Symfony;

    $symfony = new Symfony($httpDispatcher);

.. note::

    To make invalidation work, you need to :doc:`use the EventDispatchingHttpCache <symfony-cache-configuration>`.

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

The Multiplexer client allows multiple Proxy clients to be used during the standard
cache invalidation, thus enabling multiple caches to be handled at once.
It is useful when multiple caches exist in the environment and they need to be handled
at the same time; the Multiplexer proxy client will forward the cache invalidation calls
to all proxy clients provided at creation time::

    use FOS\HttpCache\ProxyClient\MultiplexerClient;
    use FOS\HttpCache\ProxyClient\Nginx;
    use FOS\HttpCache\ProxyClient\Symfony;

    $nginxClient = new Nginx($servers);
    $symfonyClient = new Symfony([...]);
    // Expects an array of ProxyClient in the constructor
    $client = new MultiplexerClient([$nginxClient, $symfonyClient]);

.. note::

    Having multiple layers of HTTP caches in place is not a good idea in general. The
    MultiplexerClient is provided for special situations, for example during a transition
    phase of an application where an old and a new system run in parallel.

Using the Proxy Client
----------------------

The recommended usage of the proxy client is to create an instance of
``CacheInvalidator`` with the correct client for your setup. See
:doc:`cache-invalidator` for more information.

Implementation Notes
--------------------

Each client is an implementation of :source:`ProxyClient <src/ProxyClient/ProxyClient.php>`.
All other interfaces, ``PurgeCapable``, ``RefreshCapable``, ``BanCapable`` and
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
