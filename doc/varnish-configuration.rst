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

Add the following to your Varnish configuration to enable :ref:`cache tagging <tags>`.

.. note::

    The custom ``X-Cache-Tags`` header should match the tagging header
    :ref:`configured in the cache invalidator <custom_tags_header>`.

.. literalinclude:: ../tests/Functional/Fixtures/varnish/ban.vcl
    :language: c
    :emphasize-lines: 8-13
    :linenos:

.. _varnish user context:

User Context
~~~~~~~~~~~~

.. sidebar:: Caching User Specific Content

    By default, Varnish does not check for cached data as soon as the request
    has a ``Cookie`` or ``Authorization`` header, as per the `default VCL`_.
    For the user context, you make Varnish cache even when there are
    credentials present.

    You need to be very careful when doing this: Your application is
    responsible for properly specifying what may or may not be shared. If a
    content only depends on the hash, ``Vary`` on the header containing the
    hash and set a ``Cache-Control`` header to make Varnish cache the request.
    If the response is individual however, you need to ``Vary`` on the
    ``Cookie`` and/or ``Authorization`` header and probably want to send a
    header like ``Cache-Control: s-maxage=0`` to prevent Varnish from caching.

To support :doc:`user context hashing <user-context>` you need to add some logic
to the ``recv`` and the ``deliver`` methods:

.. literalinclude:: ../tests/Functional/Fixtures/varnish/user_context.vcl
    :language: c
    :emphasize-lines: 8-13
    :linenos:

Your backend application should respond to the ``application/vnd.fos.user-context-hash``
request with :ref:`a proper user hash <return context hash>`.

.. note::

    If you want the context hash to be cached, you need to set the ``req.url`` to
    always the same URL, or Varnish will cache every hash lookup separately.

    However, if you have a :ref:`paywall scenario <paywall_usage>`, you need to
    leave the original URL unchanged.

.. _cookie_header:

Cleaning the Cookie Header
^^^^^^^^^^^^^^^^^^^^^^^^^^

In the examples above, an unaltered Cookie header is passed to the backend to
use for determining the user context hash. However, cookies as they are sent
by a browser are unreliable. For instance, when using Google Analytics, cookie
values are different for each request. Because of this, the hash request would
not be cached, but multiple hashes would be generated for one and the same user.

To make the hash request cacheable, you must extract a stable user session id.
You can do this as
`explained in the varnish documentation <https://www.varnish-cache.org/trac/wiki/VCLExampleRemovingSomeCookies#RemovingallBUTsomecookies>`_:

.. code-block:: c

    sub vcl_recv {
        # ...

        set req.http.cookie = ";" + req.http.cookie;
        set req.http.cookie = regsuball(req.http.cookie, "; +", ";");
        set req.http.cookie = regsuball(req.http.cookie, ";(PHPSESSID)=", "; \1=");
        set req.http.cookie = regsuball(req.http.cookie, ";[^ ][^;]*", "");
        set req.http.cookie = regsuball(req.http.cookie, "^[; ]+|[; ]+$", "");

        # ...
    }

.. note::

    If your application’s user authentication is based on a cookie other than
    PHPSESSID, change ``PHPSESSID`` to your cookie name.

.. _varnish_debugging:

Debugging
~~~~~~~~~

Configure your Varnish to set a debug header that shows whether a cache hit or miss occurred:

.. literalinclude:: ../tests/Functional/Fixtures/varnish/debug.vcl
    :language: c

.. _`default VCL`: https://www.varnish-cache.org/trac/browser/bin/varnishd/default.vcl?rev=3.0#L63
