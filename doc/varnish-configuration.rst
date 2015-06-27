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

Provided Vcl Subroutines
~~~~~~~~~~~~~~~~~~~~~~~~

In order to ease configuration we provide a set of vcl subroutines in the /config directory.
These can be directly included into ``your_varnish.vcl`` and the needed subroutines called
from the respective vcl_* subroutines.

.. important::
    When including one of the provided vcl you need to call all the defined subroutines
    or your configuration will not be valid.

    See the respective sections below on how to configure usage of each of the provided vcl's.

Purge
~~~~~

To configure Varnish for `handling PURGE requests <https://www.varnish-cache.org/docs/3.0/tutorial/purging.html>`_:

Purge removes a specific URL (including query strings) in all its variants (as specified by the ``Vary`` header).

Subroutines are provided in ``config/varnis-version/fos_purge.vcl``.

To enable support add the following to ``your_varnish.vcl``:

.. code-block:: varnish3

    include "path-to-config/varnish-[version]/fos_purge.vcl";

.. configuration-block::
    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/fos.vcl
        :language: varnish4
        :lines: 17,19,21-22
    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/fos.vcl
        :language: varnish3
        :lines: 15,17,19-20,25-32

Refresh
~~~~~~~

If you want to invalidate cached objects by `forcing a refresh <https://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh>`_
add the following to your Varnish configuration:

Refresh invalidates a specific URL including the query string, but *not* its variants.

Subroutines are provided in ``fos_refresh.vcl``

To enable support add the following to ``your_varnish.vcl``:

.. code-block:: varnish3

    include "path-to-config/varnish-[version]/fos_refresh.vcl";

.. configuration-block::
    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/fos.vcl
        :language: varnish4
        :lines: 17,20-22

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/fos.vcl
        :language: varnish3
        :lines: 15,18-20

Ban
~~~

To configure Varnish for `handling BAN requests <https://www.varnish-software.com/static/book/Cache_invalidation.html#banning>`_:

Subroutines are provided in ``fos_ban.vcl``

To enable support add the following to ``your_varnish.vcl``:

.. code-block:: varnish3

    include "path-to-config/varnish-[version]/fos_ban.vcl";

.. configuration-block::
    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/fos.vcl
        :language: varnish4
        :lines: 17-18,21-29

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/fos.vcl
        :language: varnish3
        :lines: 15-16,19-24,33-35

Varnish contains a `ban lurker`_ that crawls the content to eventually throw out banned data even when it’s not requested by any client.

.. _ban lurker: https://www.varnish-software.com/blog/ban-lurker

.. _varnish_tagging:

Tagging
~~~~~~~

If you have included fos_ban.vcl, tagging will be automatically enabled using a ``X-Cache-Tags`` header :ref:`cache tagging <tags>`.

.. note::
    If you need to use a different tag for the headers than the default ``X-Cache-Tags`` used in ``fos_ban.vcl``,
    you need to write your own VCL code and change the tagging header :ref:`configured in the cache invalidator <custom_tags_header>`.

.. configuration-block::

    .. literalinclude:: ../config/varnish-4/fos_ban.vcl
        :language: varnish4
        :emphasize-lines: 8-13,40-41
        :linenos:

    .. literalinclude:: ../config/varnish-3/fos_ban.vcl
        :language: varnish3
        :emphasize-lines: 8-13,40-41
        :linenos:

.. _varnish user context:

User Context
~~~~~~~~~~~~

To support :doc:`user context hashing <user-context>` you need to add some logic
to the ``recv`` and the ``deliver`` methods:

Subroutines are provided in ``fos_user_context.vcl``.

To enable support add the following to ``your_varnish.vcl``:

.. code-block:: varnish3

    include "path-to-config/varnish-[version]/fos_user_context.vcl";

.. configuration-block::
    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/user_context.vcl
        :language: varnish4
        :lines: 3-

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/user_context.vcl
        :language: varnish3
        :lines: 3-

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
    and Symfony2 has to look into that header to support IIS.

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

Subroutines are provided in ``fos_debug.vcl``.

To enable support add the following to ``your_varnish.vcl``:

.. code-block:: varnish3

    include "path-to-config/varnish-[version]/fos_debug.vcl";

.. configuration-block::
    .. literalinclude:: ../tests/Functional/Fixtures/varnish-4/user_context.vcl
        :language: varnish4
        :lines: 12,13,15

    .. literalinclude:: ../tests/Functional/Fixtures/varnish-3/user_context.vcl
        :language: varnish3
        :lines: 12,13,15

.. _`builtin VCL`: https://www.varnish-cache.org/trac/browser/bin/varnishd/builtin.vcl?rev=4.0
.. _`default VCL`: https://www.varnish-cache.org/trac/browser/bin/varnishd/default.vcl?rev=3.0

