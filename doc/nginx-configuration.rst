Nginx Configuration
-------------------

Below you will find detailed Nginx configuration recommendations for the
features provided by this library. The examples are tested with Nginx version
1.4.6.

Purge
~~~~~

Nginx does not support :term:`purge` requests out of the box. The
`ngx_cache_purge <https://github.com/FRiCKLE/ngx_cache_purge>`_ adds some support.

.. note::

    The Nginx *purge* does not remove variants, only the page matching the
    request.

You could use the refresh method (see below) which is easier to set up and
provides the same invalidation semantics, additionally preparing the cache with
the new content.

Unfortunately, you need to compile Nginx yourself to add the module.
For more information:

* see `this tutorial <http://mcnearney.net/blog/2010/2/28/compiling-nginx-cache-purging-support/>`_
  by Lance McNearney
* on Debian systems, you can run `install-nginx.sh <../../../tests/install-nginx.sh>`_
  to compile Nginx the same way this library is tested on Travis.

Then configure Nginx for purge requests:

.. literalinclude:: ../tests/Functional/Fixtures/nginx/fos.conf
    :language: nginx
    :linenos:
    :emphasize-lines: 41, 47-53

Please refer to the `ngx_cache_purge module documentation <https://github.com/FRiCKLE/ngx_cache_purge>`_
for more on configuring Nginx to support purge requests.

Refresh
~~~~~~~

If you want to invalidate cached objects by forcing a :term:`refresh`
you have to use the built-in `proxy_cache_bypass <http://wiki.nginx.org/HttpProxyModule#proxy_cache_bypass/>`_
operation.

There are many ways to have a request bypass the cache. This library uses a
custom HTTP header named ``X-Refresh``. Add the following to the ``location``
section:

.. literalinclude:: ../tests/Functional/Fixtures/nginx/fos.conf
    :language: nginx
    :lines: 44

