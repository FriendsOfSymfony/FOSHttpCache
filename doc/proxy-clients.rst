Proxy Client Setup
==================

This library ships with clients for the Varnish and NGINX caching servers and
the Symfony built-in HTTP cache. A Noop client that implements the interfaces
but does nothing at all is provided for local development and testing purposes.

A Multiplexer client is also available to forward calls to multiple proxy clients.

The recommended usage is to have your application interact with the
:doc:`cache invalidator <cache-invalidator>` which you set up with the proxy
client suitable for the proxy server you use.

.. _client setup:

Setup
-----

The proxy client uses a HTTP client to send requests to the proxy server.
FOSHttpCache uses the Httplug_ abstraction to not tie itself to a specific
client implementation.

The proxy client uses `Httplug discovery_` to find a suitable HTTP client. If
you need more control, see the section on :ref:`HTTP Client Configuration <HTTP client configuration>`
at the end of this chapter.

.. _varnish client:

Varnish Client
~~~~~~~~~~~~~~

At minimum, supply an array containing IPs or hostnames of the Varnish servers
that you want to send invalidation requests to. Make sure to include the port
Varnish runs on if it is not port 80::

    use FOS\HttpCache\ProxyClient\Varnish;

    $servers = ['10.0.0.1', '10.0.0.2:6081']; // Port 80 assumed for 10.0.0.1
    $varnish = new Varnish($servers);

This is sufficient for invalidating absolute URLs. If you want to use relative
paths in invalidation requests, supply the hostname and possibly a base path to
your website as ``base_uri`` option::

    $varnish = new Varnish($servers, ['base_uri' => 'my-cool-app.com']);

Again, if your web application is accessed on a port other than 80, make sure to
include that port in the base URL::

    $varnish = new Varnish($servers, ['base_uri' => 'my-cool-app.com:8080']);

.. _varnish_custom_tags_header:

The other options for the Varnish client are:

* ``tags_header`` (X-Cache-Tags): Allows you to change the HTTP header used for
  tagging. If you change this, make sure to use the correct header name in your
  :doc:`proxy server configuration <proxy-configuration>`;
* ``header_length`` (7500): Control the maximum header size when invalidating
  tags. If there are more tags to invalidate than fit into the header, the
  invalidation request is split into several requests.

A full example could look like this::

    $options = [
        'base_uri' => 'example.com',
        'tags_header' => 'X-Custom-Tags-Header',
        'header_length' => 4000,
    ];

    $varnish = new Varnish($servers, $options, $adapter);

.. note::

    To make invalidation work, you need to :doc:`configure Varnish <varnish-configuration>` accordingly.

NGINX Client
~~~~~~~~~~~~

At minimum, supply an array containing IPs or hostnames of the NGINX servers
that you want to send invalidation requests to. Make sure to include the port
NGINX runs on if it is not the default::

    use FOS\HttpCache\ProxyClient\Nginx;

    $servers = ['10.0.0.1', '10.0.0.2:8088']; // Port 80 assumed for 10.0.0.1
    $nginx = new Nginx($servers);

This is sufficient for invalidating absolute URLs. If you also wish to
invalidate relative paths, supply the hostname (or base URL) where your website
is available as the second parameter::

    $nginx = new Nginx($servers, 'my-cool-app.com');

If you have configured NGINX to support purge requests at a separate location,
call `setPurgeLocation()`::

    use FOS\HttpCache\ProxyClient\Nginx;

    $nginx = new Nginx($servers, $baseUri);
    $nginx->setPurgeLocation('/purge');


.. note::

    To use the client, you need to :doc:`configure NGINX <nginx-configuration>` accordingly.

Symfony Client
~~~~~~~~~~~~~~

At minimum, supply an array containing IPs or hostnames of your web servers
running Symfony. Provide the direct access to the web server without any other
proxies that might block invalidation requests. Make sure to include the port
the web server runs on if it is not the default::

    use FOS\HttpCache\ProxyClient\Symfony;

    $servers = ['10.0.0.1', '10.0.0.2:8088']; // Port 80 assumed for 10.0.0.1
    $client = new Symfony($servers);

This is sufficient for invalidating absolute URLs. If you also wish to
invalidate relative paths, supply the hostname (or base URL) where your website
is available as the second parameter::

    $client = new Symfony($servers, 'my-cool-app.com');

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
to all Proxy clients provided at creation time.::

    use FOS\HttpCache\ProxyClient\MultiplexerClient;
    use FOS\HttpCache\ProxyClient\Nginx;
    use FOS\HttpCache\ProxyClient\Symfony;

    $nginxClient = new Nginx($servers);
    $symfonyClient = new Symfony([...]);
    // Expects an array of ProxyClientInterface in the constructor
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

.. _HTTP client configuration:

HTTP Client Configuration
-------------------------

To avoid automatic `Httplug discovery`_, you can pass a HTTP client instance
to the proxy client. Learn more about available HTTP clients `in the Httplug documentation`_.
To customize the behavior of the HTTP client, you can use `Httplug plugins`_

The proxy client also uses the Httplug `message factory and URI factory`_. You
can pass those to the constructor as well, if you don't want it to use discovery.

The full constructor looks like this (for Varnish, NGINX and Symfony client
have the same constructor)::

    use FOS\HttpCache\ProxyClient\Varnish;

    $httpClient = ...
    $messageFactory = ...
    $streamFactory = ...

    $proxyClient = new Varnish($servers, $options, $httpClient, $messageFactory, $streamFactory);

Implementation Notes
--------------------

Each client is an implementation of :source:`ProxyClientInterface <src/ProxyClient/ProxyClientInterface.php>`.
All other interfaces, ``PurgeInterface``, ``RefreshInterface`` and ``BanInterface``
extend this ``ProxyClientInterface``. So each client implements at least one of
the three :ref:`invalidation methods <invalidation methods>` depending on the
proxy server’s abilities. To interact with a proxy client directly, refer to the
doc comments on the interfaces.

The ``ProxyClientInterface`` has one method: ``flush()``. After collecting
invalidation requests, ``flush()`` needs to be called to actually send the
requests to the proxy server. This is on purpose: this way, we can send
all requests together, reducing the performance impact of sending invalidation
requests.

.. _Httplug: http://httplug.io/
.. _Httplug discovery: http://php-http.readthedocs.io/en/latest/discovery.html
.. _in the Httplug documentation: http://php-http.readthedocs.io/en/latest/clients.html
.. _Httplug plugins: http://php-http.readthedocs.io/en/latest/plugins/index.html
.. _message factory and URI factory: http://php-http.readthedocs.io/en/latest/message/message-factory.html
