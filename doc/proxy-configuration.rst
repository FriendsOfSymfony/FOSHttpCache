Caching Proxy Configuration
===========================

Varnish Configuration
---------------------

Below you will find detailed Varnish configuration recommendations for the
features provided by this library. The examples are tested with Varnish
version 3.0.

Basic Varnish Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~

To invalidate cached objects in Varnish, begin by adding an
`ACL <https://www.varnish-cache.org/docs/3.0/tutorial/vcl.html#example-3-acls>`_
to your Varnish configuration. This ACL determines which IPs are allowed to
issue invalidation requests. Let’s call the ACL `invalidators`. The ACL below
will be used throughout the Varnish examples on this page.

.. code-block:: c

    # /etc/varnish/your_varnish.vcl

    acl invalidators {
      "localhost";
      # Add any other IP addresses that your application runs on and that you
      # want to allow invalidation requests from. For instance:
      # "192.168.1.0"/24;
    }

.. important::

    Make sure that all web servers running your application that may
    trigger invalidation are whitelisted here. Otherwise, lost cache invalidation
    requests will lead to lots of confusion.

Purge
~~~~~

To configure Varnish for `handling PURGE requests <https://www.varnish-cache.org/docs/3.0/tutorial/purging.html>`_:

.. literalinclude:: ../tests/Functional/Fixtures/varnish/purge.vcl
    :language: c

Refresh
~~~~~~~

If you want to invalidate cached objects by `forcing a refresh <https://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh>`_
add the following to your Varnish configuration:

.. literalinclude:: ../tests/Functional/Fixtures/varnish/refresh.vcl
    :language: c


Ban
~~~

To configure Varnish for `handling BAN requests <https://www.varnish-software.com/static/book/Cache_invalidation.html#banning>`_:

.. literalinclude:: ../tests/Functional/Fixtures/varnish/ban.vcl
    :language: c
    :lines: 1-7, 15-18, 20-

Tagging
~~~~~~~

Add the following to your Varnish configuration to enable :doc:`cache tagging <cache-invalidator>`.
The custom ``X-Cache-Tags`` header should match the tagging header
:doc:`configured in the cache invalidator <cache-invalidator>`.

.. literalinclude:: ../tests/Functional/Fixtures/varnish/ban.vcl
    :language: c
    :emphasize-lines: 8-13
    :linenos:

User Context
~~~~~~~~~~~~

To configure your Varnish to support :doc:`user context hashing <user-context>`:

.. literalinclude:: ../tests/Functional/Fixtures/varnish/user_context.vcl
    :language: c

Extracting the user identifier
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In the example above, we set the unique user id to the plain value of the
cookie:

.. code-block:: c

    set req.http.X-User-Id = req.http.cookie;

However, in some situations, for instance when using Google Analytics, cookie
values are different for each request. Because of this, the hash request will
not be cached. To make that request cacheable, we must extract a stable session
id and store that in the ``X-User-Id`` header.

+We can do this as [explained in the varnish documentation](https://www.varnish-cache.org/trac/wiki/VCLExampleRemovingSomeCookies#RemovingallBUTsomecookies):

.. code-block:: c

    set req.http.X-User-Id = ";" + req.http.cookie;
    set req.http.X-User-Id = regsuball(req.http.X-User-Id, "; +", ";");
    set req.http.X-User-Id = regsuball(req.http.X-User-Id, ";(PHPSESSID)=", "; \1=");
    set req.http.X-User-Id = regsuball(req.http.X-User-Id, ";[^ ][^;]*", "");
    set req.http.X-User-Id = regsuball(req.http.X-User-Id, "^[; ]+|[; ]+$", "");

You also need to change the Vary header in the hash response:

.. code-block:: php

    header('Vary: X-User-Id');

If your application’s user authentication is based on cookie other than
PHPSESSID, change ``PHPSESSID`` to your cookie name.

.. _varnish_debugging:

Debugging
~~~~~~~~~

Configure your Varnish to set a debug header that shows whether a cache hit or miss occurred:

.. literalinclude:: ../tests/Functional/Fixtures/varnish/debug.vcl
    :language: c


Nginx configuration
-------------------

Below you will find detailed Nginx configuration recommendations for the
features provided by this library. The examples are tested with Nginx version
1.4.6.

Purge
~~~~~

Nginx does not support :ref:`purge requests <purge>` out of the box. The
`ngx_cache_purge <https://github.com/FRiCKLE/ngx_cache_purge>`_ adds some support.

You could use the refresh method (see below) which is easier to set up and
provides the same invalidation semantics, additionally preparing the cache with
the new content.

Unfortunately, you need to compile Nginx yourself to add the module.
For more information:

* see `this tutorial <http://mcnearney.net/blog/2010/2/28/compiling-nginx-cache-purging-support/>`_
  by Lance McNearney
* on Debian systems, you can run `install-nginx.sh <../../../tests/install-nginx.sh>`_
  to compile Nginx as in the way this library is tested on Travis.

.. note::

    The Nginx *purge* does not remove variants, only the page matching the
    request.

Please refer to the `ngx_cache_purge module documentation <https://github.com/FRiCKLE/ngx_cache_purge>`_
for more on configuring Nginx to support purge requests.

Refresh
~~~~~~~

If you want to invalidate cached objects by :ref:`forcing a refresh <refresh>`
you have to use the built-in `proxy_cache_bypass <http://wiki.nginx.org/HttpProxyModule#proxy_cache_bypass/>`_
operation.

There are many ways to have a request bypass the cache. This library uses a
custom HTTP header named ``X-Refresh``.
