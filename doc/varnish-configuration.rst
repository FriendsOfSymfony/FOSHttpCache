.. _varnish configuration:

Varnish Configuration
---------------------

Below you will find detailed Varnish configuration recommendations for the
features provided by this library. The configuration is provided for Varnish 3
and 4.

Basic Varnish Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~

To invalidate cached objects in Varnish, begin by adding an
`ACL <https://www.varnish-cache.org/docs/3.0/tutorial/vcl.html#example-3-acls>`_
to your Varnish configuration. This ACL determines which IPs are allowed to
issue invalidation requests. Let’s call the ACL `invalidators`. The ACL below
will be used throughout the Varnish examples on this page.

.. code-block:: varnish4

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

Purge removes a specific URL (including query strings) in all its variants (as specified by the ``Vary`` header).

.. configuration-block::

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/purge.vcl
        :language: varnish4
        :linenos:

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/purge.vcl
        :language: varnish3
        :linenos:

Refresh
~~~~~~~

If you want to invalidate cached objects by `forcing a refresh <https://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh>`_
add the following to your Varnish configuration:

Refresh invalidates a specific URL including the query string, but *not* its variants.

.. literalinclude:: ../tests/Functional/Fixtures/varnish-3/refresh.vcl
    :language: varnish3
    :linenos:

Ban
~~~

To configure Varnish for `handling BAN requests <https://www.varnish-software.com/static/book/Cache_invalidation.html#banning>`_:

.. configuration-block::

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/ban.vcl
        :language: varnish4
        :lines: 1-7, 15-18, 20-
        :linenos:

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/ban.vcl
        :language: varnish3
        :lines: 1-7, 15-18, 20-
        :linenos:

Varnish contains a `ban lurker`_ that crawls the content to eventually throw out banned data even when it’s not requested by any client.

.. _ban lurker: https://www.varnish-software.com/blog/ban-lurker

.. _varnish_tagging:

Tagging
~~~~~~~

Add the following to your Varnish configuration to enable :ref:`cache tagging <tags>`.

.. note::

    The custom ``X-Cache-Tags`` header should match the tagging header
    :ref:`configured in the cache invalidator <custom_tags_header>`.

.. configuration-block::

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/ban.vcl
        :language: varnish4
        :emphasize-lines: 8-13,39
        :linenos:

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/ban.vcl
        :language: varnish3
        :emphasize-lines: 8-13,39
        :linenos:

.. _varnish user context:

User Context
~~~~~~~~~~~~

To support :doc:`user context hashing <user-context>` you need to add some logic
to the ``recv`` and the ``deliver`` methods:

.. configuration-block::

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/user_context.vcl
        :language: varnish4
        :lines: 3-29, 33-
        :linenos:

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/user_context.vcl
        :language: varnish3
        :lines: 1-27, 31-
        :linenos:

.. sidebar:: Caching User Specific Content

    By default, Varnish does not check for cached data as soon as the request
    has a ``Cookie`` or ``Authorization`` header, as per the `builtin VCL`_
    (for Varnish 3, see `default VCL`_). For the user context, you make Varnish
    cache even when there are credentials present.

    You need to be very careful when doing this: Your application is
    responsible for properly specifying what may or may not be shared. If a
    content only depends on the hash, ``Vary`` on the header containing the
    hash and set a ``Cache-Control`` header to make Varnish cache the request.
    If the response is individual however, you need to ``Vary`` on the
    ``Cookie`` and/or ``Authorization`` header and probably want to send a
    header like ``Cache-Control: s-maxage=0`` to prevent Varnish from caching.

Your backend application should respond to the ``application/vnd.fos.user-context-hash``
request with :ref:`a proper user hash <return context hash>`.

.. note::

    We do not use ``X-Original-Url`` here, as the header will be sent to the
    backend and some applications look at this header, which would lead to
    problems. For example, the Microsoft IIS rewriting module uses this header
    and Symfony has to look into that header to support IIS.

.. note::

    If you want the context hash to be cached, you need to always set the
    ``req.url`` to the same URL, or Varnish will cache every hash lookup
    separately.

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
`explained in the Varnish documentation <https://www.varnish-cache.org/trac/wiki/VCLExampleRemovingSomeCookies#RemovingallBUTsomecookies>`_:

.. code-block:: varnish4
    :linenos:

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

Configure your Varnish to set a custom header (`X-Cache`) that shows whether a
cache hit or miss occurred. This header will only be set if your application
sends an `X-Cache-Debug` header:

.. configuration-block::

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/debug.vcl
        :language: varnish4
        :linenos:

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/debug.vcl
        :language: varnish3
        :linenos:

.. _`builtin VCL`: https://www.varnish-cache.org/trac/browser/bin/varnishd/builtin.vcl?rev=4.0
.. _`default VCL`: https://www.varnish-cache.org/trac/browser/bin/varnishd/default.vcl?rev=3.0

