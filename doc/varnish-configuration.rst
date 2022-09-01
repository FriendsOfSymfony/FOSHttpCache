.. _varnish configuration:

Varnish Configuration
---------------------

Below you will find detailed Varnish configuration recommendations for the
features provided by this library. The configuration is provided for Varnish 3,
4 and 5.

Basic Varnish Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~

To invalidate cached objects in Varnish, begin by adding an `ACL`_ (for Varnish
3 see `ACL for Varnish 3`_) to your Varnish configuration. This ACL determines
which IPs are allowed to issue invalidation requests. To use the provided
configuration fragments, this ACL has to be named ``invalidators``. The most
simple ACL, valid for Varnish version 3 or better, looks as follows:

.. code-block:: varnish3

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

Provided VCL Subroutines
~~~~~~~~~~~~~~~~~~~~~~~~

In order to ease configuration we provide a set of VCL subroutines in the
``resources/config`` `directory of FOSHttpCache`_. These can be included from
your main Varnish configuration file, typically ``default.vcl``. Then you need
to make your ``vcl_*`` subroutines call the ``fos_*`` routines.

.. tip::
    When including one of the provided VCL, you need to call all the defined
    subroutines or your configuration will not be valid.

    See the respective sections below on how to configure usage of each of the
    provided VCLs.

Purge
~~~~~

Purge removes a specific URL (including query strings) in all its variants (as
specified by the ``Vary`` header).

Subroutines are provided in ``resources/config/varnish-[version]/fos_purge.vcl``.
To enable this feature, add the following to ``your_varnish.vcl``:

.. configuration-block::

    .. code-block:: varnish4

        include "path-to-config/varnish/fos_purge.vcl";

        sub vcl_recv {
            call fos_purge_recv;
        }

    .. code-block:: varnish3

        include "path-to-config/varnish-3/fos_purge.vcl";

        sub vcl_recv {
            call fos_purge_recv;
        }

        sub vcl_hit {
            call fos_purge_hit;
        }

        sub vcl_miss {
            call fos_purge_miss;
        }

Read more on `handling PURGE requests`_ in the Varnish documentation (for
Varnish 3, see `purging for Varnish 3`_).

Refresh
~~~~~~~

Refresh fetches a page from the backend even if it would still be in the cache,
resulting in an updated cache without a cache miss on the next request.

Refreshing applies only to a specific URL including the query string, but *not*
its variants.

Subroutines are provided in ``resources/config/varnish-[version]/fos_refresh.vcl``.
To enable this feature, add the following to ``your_varnish.vcl``:

.. configuration-block::

    .. code-block:: varnish4

        include "path-to-config/varnish/fos_refresh.vcl";

        sub vcl_recv {
            call fos_refresh_recv;
        }

    .. code-block:: varnish3

        include "path-to-config/varnish-3/fos_refresh.vcl";

        sub vcl_recv {
            call fos_refresh_recv;
        }

Read more on `forcing a refresh`_ in the Varnish documentation (for Varnish 3,
see `refreshing for Varnish 3`_).

Ban
~~~

Banning invalidates whole groups of cached entries with regular expressions.

Subroutines are provided in ``resources/config/varnish-[version]/fos_ban.vcl``
To enable this feature, add the following to ``your_varnish.vcl``:

.. configuration-block::

    .. code-block:: varnish4

        include "path-to-config/varnish/fos_ban.vcl";

        sub vcl_recv {
            call fos_ban_recv;
        }

        sub vcl_backend_response {
            call fos_ban_backend_response;
        }

        sub vcl_deliver {
            call fos_ban_deliver;
        }

    .. code-block:: varnish3

        include "path-to-config/varnish-3/fos_ban.vcl";

        sub vcl_recv {
            call fos_ban_recv;
        }

        sub vcl_fetch {
            call fos_ban_fetch;
        }

        sub vcl_deliver {
            call fos_ban_deliver;
        }

This subroutine also sets the ``X-Url`` and ``X-Host`` headers on the cache
object. These headers are used by the Varnish `ban lurker`_ that crawls the
content to eventually throw out banned data even when it’s not requested by any
client. Read more on `handling BAN requests`_ in the Varnish documentation (for
Varnish 3, see `banning for Varnish 3`_).

.. _varnish_tagging:

Tagging
~~~~~~~

Feature: :ref:`cache tagging <tags>`

For this feature you need to choose between either BAN _(default)_ and the
more performant xkey based tag system.

Tag Invalidation Using xkey
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Since Varnish 4.1, you can use the official `xkey Varnish module`_ for better
performance with cache tags.

.. tip::

    If you are using the payed "Varnish Plus" offering, you should use the
    improved ykey feature instead of xkey. The `ykey documentation`_ explains
    how to use the xkey headers to set up the ykey information. Instead of
    including the ``fos_tags_xkey.vcl`` you would need to write your own VCL to
    handle ykey tagging and invalidation requests.

As explained in the `Cache Invalidation chapter of the Varnish documentation`_::
> (..), ``hashtwo``/``xkey`` is much more efficient than bans because of two reasons:
> 1) looking up hash keys is much more efficient than traversing ban-lists, and
> 2) every time you test a ban expression, it checks every object in the cache
> that is older than the ban itself.

With xkey, you can also soft purge tagged responses to allow for a grace period
where invalidated (stale) content is still delivered to the client.

Install the varnish modules with your distribution if available, or refer to the
Varnish documentation for `installing xkey`_. Version 0.10.2 is the first version
to support purging more than one tag at once. With older versions you can only
invalidate one tag at a time, and soft purge is not available.

To use ``xkey``, :ref:`configure the Varnish Client for xkey <varnish_custom_tags_header>`
and :ref:`the response tagger to use the xkey header <response_tagger_optional_parameters>`,
and include ``resources/config/varnish/fos_tags_xkey.vcl`` in your VCL:

.. code-block:: varnish4

    include "path-to-config/varnish/fos_tags_xkey.vcl";

    sub vcl_recv {
        call fos_tags_xkey_recv;
    }

    sub vcl_deliver {
        call fos_tags_xkey_deliver;
    }

Note that there is no xkey VCL file for Varnish version 3 because the
varnish-modules are only available for Varnish 4.1 or newer.

Tag Invalidation Using BAN
^^^^^^^^^^^^^^^^^^^^^^^^^^

If you have included ``fos_ban.vcl``, tagging will be automatically enabled
with the ``X-Cache-Tags`` header for both marking the tags on the response and
for the invalidation request to tell what tags to invalidate.

If you use a different name for :doc:`response tagging <response-tagging>` than
the default ``X-Cache-Tags`` or a different name for specifying which tags to
invalidate in your :ref:`cache invalidator configuration <varnish_custom_tags_header>`
you have to write your own VCL code for tag invalidation. Your custom VCL will
look like this:

.. configuration-block::

    .. literalinclude:: ../resources/config/varnish/fos_ban.vcl
        :language: varnish4
        :emphasize-lines: 17-23,50-51
        :linenos:

    .. literalinclude:: ../resources/config/varnish-3/fos_ban.vcl
        :language: varnish3
        :emphasize-lines: 17-23,50-51
        :linenos:

.. hint::

    The line you need to adjust from the code above is line 21. The left side
    is the header used to tag the response, the right side is the header used
    when sending invalidation requests. If you change one or the other header
    name, make sure to adjust the configuration accordingly.

.. _varnish user context:

User Context
~~~~~~~~~~~~

Feature: :doc:`user context hashing <user-context>`

The ``fos_user_context.vcl`` needs the ``user_context_hash_url`` subroutine
that sets the URL to do the hash lookup. The default URL is
``/_fos_user_context_hash`` and you can simply include
``resources/config/varnish-[version]/fos_user_context_url.vcl`` in your
configuration to provide this. If you need a different URL, write your own
``user_context_hash_url`` subroutine instead.

.. tip::

    The provided VCL to fetch the user hash restarts GET/HEAD requests. It
    would be more efficient to do the hash lookup request with curl, using the
    `curl Varnish plugin`_. If you can enable curl support, the recommended way
    is to implement your own VCL to do a curl request for the hash lookup
    instead of using the VCL provided here.

    Also note that restarting a GET request leads to Varnish discarding the
    body of the request. If you have some special case where you have GET
    requests with a body, use curl.

To enable this feature, add the following to ``your_varnish.vcl``:

.. configuration-block::

    .. code-block:: varnish4

        include "path-to-config/varnish/fos_user_context.vcl";
        include "path-to-config/varnish/fos_user_context_url.vcl";

        sub vcl_recv {
            call fos_user_context_recv;
        }

        sub vcl_hash {
            call fos_user_context_hash;
        }

        sub vcl_backend_response {
            call fos_user_context_backend_response;
        }

        sub vcl_deliver {
            call fos_user_context_deliver;
        }

    .. code-block:: varnish3

        include "path-to-config/varnish-3/fos_user_context.vcl";
        include "path-to-config/varnish/fos_user_context_url.vcl";

        sub vcl_recv {
            call fos_user_context_recv;
        }

        sub vcl_hash {
            call fos_user_context_hash;
        }

        sub vcl_fetch {
            call fos_user_context_fetch;
        }

        sub vcl_deliver {
            call fos_user_context_deliver;
        }

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

Your backend application needs to respond to the ``application/vnd.fos.user-context-hash``
request with :ref:`a proper user hash <return context hash>`.

.. tip::

    The provided VCL assumes that you want the context hash to be cached, so we
    set the ``req.url`` to a fixed URL. Otherwise Varnish would cache every
    hash lookup separately.

    The ``fos_user_context_hash`` should be used to separate the cache of the
    hash lookup. If you don't do that, Varnish can run into performance issues
    because the user hash lookup creates a `large number of variants`_. If your
    hash is taking into account other headers than ``Authorization`` and
    ``Cookie``, create your own ``vcl_hash`` function that adds all those
    headers to ``hash_data`` for user context hash lookup requests.

    However, if you have a :ref:`paywall scenario <paywall_usage>`, you need to
    leave the original URL unchanged. For that case, you would need to write
    your own VCL.

.. _cookie_header:
.. _varnish_cookie_header:

Cleaning the Cookie Header
^^^^^^^^^^^^^^^^^^^^^^^^^^

In the examples above, an unaltered Cookie header is passed to the backend to
use for determining the user context hash. However, cookies as they are sent
by a browser are unreliable. For instance, when using Google Analytics, cookie
values are different for each request. Because of this, the hash request would
not be cached, but multiple hashes would be generated for one and the same user.

To make the hash request cacheable, you must extract a stable user session id
*before* calling ``fos_user_context_recv``. You can do this as
`explained in the Varnish documentation`_:

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

.. _varnish_customttl:

Custom TTL
~~~~~~~~~~

.. include:: includes/custom-ttl.rst

Subroutines are provided in ``resources/config/varnish-[version]/fos_custom_ttl.vcl``.
Add the following to ``your_varnish.vcl``:

.. configuration-block::

    .. code-block:: varnish4

        include "path-to-config/varnish/fos_custom_ttl.vcl";

        sub vcl_backend_response {
            call fos_custom_ttl_backend_response;
        }

    .. code-block:: varnish3

        include "path-to-config/varnish-3/fos_custom_ttl.vcl";

        sub vcl_fetch {
            call fos_custom_ttl_fetch;
        }

The custom TTL header is removed before sending the response to the client.

.. note::

    If you are using Varnish 3, this feature is using inline C. Inline C is
    enabled for Varnish 3 by default. Check for the ``vcc_allow_inline_c``
    setting.
    If you are using Varnish 4 or newer, you are using the
    ``varnish/fos_custom_ttl.vcl`` which uses a Varnish function instead of inline C.

.. _varnish_debugging:

Debugging
~~~~~~~~~

Configure your Varnish to set a custom header (``X-Cache``) that shows whether a
cache hit or miss occurred. This header will only be set if your application
sends an ``X-Cache-Debug`` header:

Subroutines are provided in ``fos_debug.vcl``.

To enable this feature, add the following to ``your_varnish.vcl``:

.. configuration-block::

    .. code-block:: varnish4

        include "path-to-config/varnish/fos_debug.vcl";

        sub vcl_deliver {
            call fos_debug_deliver;
        }

    .. code-block:: varnish3

        include "path-to-config/varnish-3/fos_debug.vcl";

        sub vcl_deliver {
            call fos_debug_deliver;
        }

.. _ACL: https://www.varnish-cache.org/docs/4.0/users-guide/vcl-example-acls.html
.. _ACL for Varnish 3: https://www.varnish-cache.org/docs/3.0/tutorial/vcl.html#example-3-acls
.. _directory of FOSHttpCache: https://github.com/FriendsOfSymfony/FOSHttpCache/tree/2.x/resources/config
.. _handling PURGE requests: https://www.varnish-cache.org/docs/4.0/users-guide/purging.html#bans
.. _purging for Varnish 3: https://www.varnish-cache.org/docs/3.0/tutorial/purging.html
.. _forcing a refresh: https://www.varnish-cache.org/docs/4.0/users-guide/purging.html#forcing-a-cache-miss
.. _refreshing for Varnish 3: https://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh
.. _handling BAN requests: https://www.varnish-cache.org/docs/4.0/users-guide/purging.html#bans
.. _banning for Varnish 3: https://www.varnish-software.com/book/3/Cache_invalidation.html#banning
.. _ban lurker: https://www.varnish-software.com/blog/ban-lurker
.. _explained in the Varnish documentation: https://www.varnish-cache.org/trac/wiki/VCLExampleRemovingSomeCookies#RemovingallBUTsomecookies
.. _curl Varnish plugin: https://github.com/varnish/libvmod-curl
.. _xkey Varnish module: https://github.com/varnish/varnish-modules/blob/master/docs/vmod_xkey.vcc
.. _ykey documentation: https://docs.varnish-software.com/varnish-cache-plus/vmods/ykey/
.. _Cache Invalidation chapter of the Varnish documentation: http://book.varnish-software.com/4.0/chapters/Cache_Invalidation.html#hashtwo-xkey-varnish-software-implementation-of-surrogate-keys
.. _installing xkey: https://github.com/varnish/varnish-modules#installation
.. _large number of variants: https://github.com/varnishcache/varnish-cache/pull/3520
.. _`builtin VCL`: https://github.com/varnishcache/varnish-cache/blob/5.0/bin/varnishd/builtin.vcl
.. _`default VCL`: https://github.com/varnishcache/varnish-cache/blob/3.0/bin/varnishd/default.vcl
