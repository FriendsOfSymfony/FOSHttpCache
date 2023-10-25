.. _fastly configuration:

Fastly Configuration
-------------------

Caching is enabled by default. All requests that are marked as cacheable will be cached.

.. note::

    Please read the `Fastly documentation <https://docs.fastly.com/>`_ carefully to set up your caching correctly.

Tagging
~~~~~~~

Fastly supports :term:`tagging` out of the box.
Configure the tag header to ``Surrogate-Keys``. (``fos_http_cache.tags.response_header`` if you use FOSHttpCacheBundle)

Purge
~~~~~

Fastly supports two types of deletion of cached items using tags:

1. Soft Purge: This is the default behavior. It will mark the cached item as stale. Stale items *may* still be served (e.g. with the ``stale-while-revalidate`` or ``stale-on-error`` cache control headers). Once the TTL expires, the stale item will be evicted from the cache and the next request must be fetched from the origin.
2. Purge: This will immediately evict the cached item from the cache and the next request will be fetched from the origin.

There are different ways to purge the cache:

1. Using Tags
2. Using URLs

Cache Header
~~~~~~~~~~~~

To specify how long items should be cached you can use the ``Surrogate-Control`` header.
Using this header enables you to use the ``Cache-Control`` header to specify the cache duration for browsers.

Currently, FOSHttpCache does not yet support the ``Surrogate-Control`` header.
To configure the ``Cache-Control`` you either have to set ``Surrogate-Control`` yourself or
configure the ``Cache-Control`` header in your Fastly configuration.

.. code-block:: none

    set beresp.http.Surrogate-Control = beresp.http.Cache-Control;
    // Add your rules here
    if (req.url ~ "\.(css|js|jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|flv|woff|woff2|svg|ico)") {
    } else {
        set beresp.http.Cache-Control = "no-store, max-age=0";
    }
