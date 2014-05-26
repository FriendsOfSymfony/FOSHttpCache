Caching Proxy Clients
=====================

This library ships with clients for the Varnish and Nginx caching proxy. You
can use the clients either wrapped by the :doc:`cache invalidator <cache-invalidator>`
(recommended), or directly for low-level access to invalidation functionality.

.. _client setup:

Setup
-----

Varnish Client
~~~~~~~~~~~~~~

At minimum, supply an array containing IPs or hostnames of the Varnish servers
that you want to send invalidation requests to. Make sure to include the port
Varnish runs on if it is not port 80.

.. code-block:: php

    use FOS\HttpCache\ProxyClient\Varnish;

    $servers = array('10.0.0.1', '10.0.0.2:6081'); // Port 80 assumed for 10.0.0.1
    $varnish = new Varnish($servers);

This is sufficient for invalidating absolute URLs. If you also wish to
invalidate relative paths, supply the hostname (or base URL) where your website
is available as the second parameter:

.. code-block:: php

    $varnish = new Varnish($servers, 'my-cool-app.com');

.. note::

    To use the client, you need to :doc:`configure Varnish <varnish-configuration>` accordingly.

Nginx Client
~~~~~~~~~~~~

At minimum, supply an array containing IPs or hostnames of the Nginx servers
that you want to send invalidation requests to. Make sure to include the port
Nginx runs on if it is not port 80.

.. code-block:: php

    use FOS\HttpCache\Invalidation\Nginx;

    $servers = array('10.0.0.1', '10.0.0.2:8088'); // Port 80 assumed for 10.0.0.1
    $nginx = new Nginx($servers);

This is sufficient for invalidating absolute URLs. If you also wish to
invalidate relative paths, supply the hostname (or base URL) where your website
is available as the second parameter:

.. code-block:: php

    $nginx = new Nginx($servers, 'my-cool-app.com');

If you have configured Nginx to support purge requests at a separate location,
supply that location to the class as the third parameter:

.. code-block:: php

    $nginx = new Nginx($servers, 'my-cool-app.com', '/purge');

.. note::

    To use the client, you need to :doc:`configure Nginx <nginx-configuration>` accordingly.

Using the Clients
-----------------

Each client is an implementation of `ProxyClientInterface <../../../src/ProxyClient/ProxyClientInterface.php>`_.
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

======== ======= ======= =======
Client   Purge   Refresh Ban
======== ======= ======= =======
Varnish  ✓       ✓       ✓
Nginx    ✓       ✓
======== ======= ======= =======

Purge
~~~~~

If the caching proxy understands :term:`purge` requests,
its client should implement ``PurgeInterface``. Use the ``purge($url)`` method to
purge one specific URL. The URL can be either an absolute URL or a relative
path:

.. code-block:: php

    $client
        ->purge('http://my-app.com/some/path')
        ->purge('/other/path')
        ->flush()
    ;

Refresh
~~~~~~~

If the caching proxy understands :term:`refresh` requests,
its client should implement ``RefreshInterface``. Use ``refresh()`` to refresh
one specific URL. The URL can be either an absolute URL or a relative path:

.. code-block:: php

    $client
        ->refresh('http://my-app.com/some/path')
        ->refresh('other/path')
        ->flush()
    ;

You can specify HTTP headers as the second argument to ``refresh()``. For
instance, to only refresh the JSON representation of an URL:

.. code-block:: php

    $client
        ->refresh('/some/path', array('Accept' => 'application/json')
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

For instance, to ban all .png files on all application hosts:

.. code-block:: php

    $client->banPath('.*png$');

To ban all HTML URLs that begin with ``/articles/``:

.. code-block:: php

    $client->banPath('/articles/.*', 'text/html');

By default, URLs will be banned on all application hosts. You can limit this by
specifying a host header:

.. code-block:: php

    $client->banPath('*.png$', null, '^www.example.com$');

If you want to go beyond banning combinations of path, content type and hostname,
use the ``ban(array $headers)`` method. This method allows you to specify any
combination of headers that should be banned. For instance, when using the
Varnish client:

.. code-block:: php

    use FOS\HttpCache\ProxyClient\Varnish;

    $varnish->ban(array(
        Varnish::HTTP_HEADER_URL   => '.*\.png$',
        Varnish::HTTP_HEADER_HOST  => '.*example\.com',
        Varnish::HTTP_HEADER_CACHE => 'my-tag',
    ));

Make sure to add any headers that you want to ban on to your
:doc:`Varnish configuration <varnish-configuration>`.
