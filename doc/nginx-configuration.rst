.. _nginx configuration:

Nginx Configuration
-------------------

Below you will find detailed Nginx configuration recommendations for the
features provided by this library. The examples are tested with Nginx version
1.4.6.

Nginx cache is a set of key/value pairs. The key is built with elements taken from the requests 
(URI, cookies, http headers etc) as specified by `proxy_cache_key` directive.

When we interact with the cache to purge/refresh entries we must send to Nginx a request which has 
the very same values, for the elements used for building the key, as the request that create the entry.
In this way Nginx can build the correct key and apply the required operation to the entry.

By default Nginx key is built with `$scheme$proxy_host$request_uri`. For a full list of the elements
you can use in the key see `this page from the official documentation <http://nginx.org/en/docs/http/ngx_http_core_module.html#variables>`_

Purge
~~~~~

Nginx does not support :term:`purge` functionality out of the box but you can easily add it with 
`ngx_cache_purge <https://github.com/FRiCKLE/ngx_cache_purge>`_ module. You just need to compile 
Nginx from sources adding `ngx_cache_purge` with `--add-module`

You can check the script :source:`install-nginx.sh <tests/install-nginx.sh>` to get an idea
about the steps you need to perform.

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
you have to use the built-in `proxy_cache_bypass <http://nginx.org/en/docs/http/ngx_http_proxy_module.html#proxy_cache_bypass>`_
directive. This directive defines conditions under which the response will not
be taken from a cache. This library uses a custom HTTP header named ``X-Refresh``,
so add a line like the following to your config:

.. literalinclude:: ../tests/Functional/Fixtures/nginx/fos.conf
    :language: nginx
    :lines: 44

