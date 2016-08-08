Caching Proxy Clients
=====================

This library ships with clients for the Varnish and NGINX caching servers and
the Symfony built-in HTTP cache. A Noop client that implements the interfaces
but does nothing at all is provided for local development and testing purposes.
You can use the clients either wrapped by the
:doc:`cache invalidator <cache-invalidator>` (recommended), or directly for
low-level access to invalidation functionality. Which client you need depends on
which caching solution you use.

.. _client setup:

Setup
-----

HTTP Client Installation
~~~~~~~~~~~~~~~~~~~~~~~~

Because the clients send invalidation requests over HTTP, an `HTTPlug client`_
must be installed. Pick either a standalone client or an adapter to a client
library that is already included in your project. For instance, if you use
Guzzle 6 in your project, install the appropriate adapter:

.. code-block:: bash

    $ composer require php-http/guzzle6-adapter

You also need a `PSR-7 message implementation`_. If you use Guzzle 6, Guzzle’s
implementation is already included. If you use another client, you need to
install one of the message implementations. Recommended:

.. code-block:: bash

    $ composer require guzzlehttp/psr7

Alternatively:

.. code-block:: bash

    $ composer require zendframework/zend-diactoros

.. _HTTP adapter configuration:

HTTP Client Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~

By default, the proxy client will automatically locate an HTTP client that you
have installed through Composer. But you can also pass the adapter explicitly.
This is most useful when you have created a HTTP client with custom options or
middleware (such as logging)::

    use GuzzleHttp\Client;

    $config = [
        // For instance, custom middlewares
    ];
    $yourHttpClient = new Client($config);

Take your client and create a HTTP adapter from it::

    use Http\Adapter\Guzzle6HttpAdapter;

    $adapter = new Guzzle6HttpAdapter($client);

Then pass that adapter to the caching proxy client::

    $proxyClient = new Varnish($servers, '/baseUrl', $adapter);
    // Varnish as example, but also possible for NGINX and Symfony

HTTP Message Factory Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Similar to the HTTP client, the HTTP message factory is automatically located
by default. You can pass an explicit instance of the message factory if you
need to.

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

* ``tags_header`` (Cache-Tags): Allows you to change the HTTP header used for
  tagging. If you change this, make sure to use the correct header name in your
  :doc:`caching proxy configuration <proxy-configuration>`;
* ``header_length`` (7500): Control the maximum header size when invalidating
  tags. If there are more tags to invalidate than fit into the header, the
  invalidation request is split into several requests.

A full example could look like this::

    $options = [
        'base_uri' => 'example.com',
        'tags_header' => 'Custom-Tags-Header',
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
environment that does not have a caching proxy set up. Rather than making the
cache invalidator optional in your code, you can (based on the environment)
determine whether to inject the real client or the Noop client. The rest of your
application then does not need to worry about the environment.

Using the Clients
-----------------

Each client is an implementation of :source:`ProxyClientInterface <src/ProxyClient/ProxyClientInterface.php>`.
All other interfaces, ``PurgeInterface``, ``RefreshInterface`` and ``BanInterface``
extend this ``ProxyClientInterface``. So each client implements at least one of
the three :ref:`invalidation methods <invalidation methods>` depending on the
caching proxy’s abilities.

The ``ProxyClientInterface`` has one method: ``flush()``. After collecting
invalidation requests, ``flush()`` needs to be called to actually send the
requests to the caching proxy. This is on purpose: this way, we can send
all requests together, reducing the performance impact of sending invalidation
requests.

Supported invalidation methods
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

============= ======= ======= =======
Client        Purge   Refresh Ban
============= ======= ======= =======
Varnish       ✓       ✓       ✓
NGINX         ✓       ✓
Symfony Cache ✓       ✓
============= ======= ======= =======

.. _proxy-client purge:

Purge
~~~~~

If the caching proxy understands :term:`purge` requests,
its client should implement ``PurgeInterface``. Use the ``purge($url)`` method to
purge one specific URL. The URL can be either an absolute URL or a relative
path::

    $client
        ->purge('http://my-app.com/some/path')
        ->purge('/other/path')
        ->flush()
    ;

You can specify HTTP headers as the second argument to ``purge()``.
For instance::

    $client
        ->purge('/some/path', ['Foo' => 'bar'])
        ->flush()
    ;

Please note that purge will invalidate all variants, so you do not have to
send any headers that you vary on, such as ``Accept``.

.. include:: includes/custom-headers.rst

.. _proxy-client refresh:

Refresh
~~~~~~~

If the caching proxy understands :term:`refresh` requests,
its client should implement ``RefreshInterface``. Use ``refresh()`` to refresh
one specific URL. The URL can be either an absolute URL or a relative path::

    $client
        ->refresh('http://my-app.com/some/path')
        ->refresh('other/path')
        ->flush()
    ;

You can specify HTTP headers as the second argument to ``refresh()``. For
instance, to only refresh the JSON representation of an URL::

    $client
        ->refresh('/some/path', ['Accept' => 'application/json'])
        ->flush()
    ;

Ban
~~~

If the caching proxy understands :term:`ban` requests,
its client should implement ``BanInterface``.

You can invalidate all URLs matching a regular expression by using the
``banPath($path, $contentType, $hosts)`` method. It accepts a regular expression
for the path to invalidate and an optional content type regular expression and
list of application hostnames.

For instance, to ban all ``.png`` files on all application hosts::

    $client->banPath('.*png$');

To ban all HTML URLs that begin with ``/articles/``::

    $client->banPath('/articles/.*', 'text/html');

By default, URLs will be banned on all application hosts. You can limit this by
specifying a host header::

    $client->banPath('*.png$', null, '^www.example.com$');

If you want to go beyond banning combinations of path, content type and hostname,
use the ``ban(array $headers)`` method. This method allows you to specify any
combination of headers that should be banned. For instance, when using the
Varnish client::

    use FOS\HttpCache\ProxyClient\Varnish;

    $varnish->ban([
        Varnish::HTTP_HEADER_URL   => '.*\.png$',
        Varnish::HTTP_HEADER_HOST  => '.*example\.com',
        Varnish::HTTP_HEADER_CACHE => 'my-tag',
    ]);

Make sure to add any headers that you want to ban on to your
:doc:`proxy configuration <proxy-configuration>`.

.. _HTTPlug client: http://httplug.io/
.. _PSR-7 message implementation: https://packagist.org/providers/psr/http-message-implementation
