.. _invalidation introduction:

An Introduction to Cache Invalidation
=====================================

This general introduction explains cache invalidation concepts. If you are
already familiar with cache invalidation, you may wish to skip this chapter.

HTTP Caching Terminology
------------------------

.. glossary::

    Client
        The client that requests web representations of the application data.
        This client can be visitor of a website, or for instance a client that
        fetches data from a REST API.

    Application
        Also *backend application* or *origin server*. The web application that
        holds the data.

    Caching proxy
        Also `reverse caching proxy <http://en.wikipedia.org/wiki/Reverse_proxy>`_.
        Examples: Varnish, NGINX.

    Time to live (TTL)
        Maximum lifetime of some content. Expressed in either an expiry date
        for the content (the ``Expires:`` header) or its maximum age (the
        ``max-age`` and ``s-maxage`` cache control directives).

    Invalidation
        Invalidating a piece of content means telling the caching proxy to no
        longer serve that content to clients. The proxy can choose to either
        discard the content immediately, or do so when it is next requested.
        On that next request, the proxy will fetch a fresh copy from the
        application.

What is Cache Invalidation?
---------------------------

.. epigraph::

    There are only two hard things in Computer Science: cache invalidation and
    naming things.

    *-- Phil Karlton*

The problem
~~~~~~~~~~~

HTTP caching is a great solution for improving the performance of your web
application. For lower load on the application and fastest response time, you
want to cache content for a long period. But at the same time, you want your
clients to see fresh content as soon as there is an update.

Instead of finding some compromise, you can have both with cache invalidation.
When application data changes, the application takes care of invalidating its
web representation as out-of-date. Although caching proxies may handle
invalidation differently, the effect is always the same: the next time a client
requests the data, he or she gets a new version instead of the outdated one.

Alternatives
~~~~~~~~~~~~

There are three alternatives to cache invalidation.

1. The first is to *expire* your cached content quickly by reducing its time to
   live (TTL). However, short TTLs cause a higher load on the application
   because content must be fetched from it more often. Moreover, reduced TTL
   does not guarantee that clients will have fresh content, especially if the
   content changes very rapidly as a result of client interactions with the
   application.

2. The second alternative is to *validate* the freshness of cached content at
   every request. Again, this means more load on your application, even if you
   return early (for instance by using HEAD requests).

3. The last resort is to *not cache* volatile content at all. While this
   guarantees the user always sees changes without delay, it obviously
   increases your application load even more.

Cache invalidation gives you the best of both worlds: you can have very long
TTLs, so when content changes little, it can be served from the cache because
no requests to your application are required. At the same time, when data
does change, that change is reflected without delay in the web representations.

Disadvantages
~~~~~~~~~~~~~

Cache invalidation has two possible downsides:

* Invalidating cached web representations when their underlying data changes
  can be very simple. For instance, invalidate ``/articles/123`` when article 123
  is updated. However, data usually is represented not in one but in multiple
  representations. Article 123 could also be represented on the articles index
  (``/articles``), the list of articles in the current year (``/articles/current``)
  and in search results (``/search?name=123``). In this case, when article 123 is
  changed, a lot more is involved in invalidating all of its representations.
  In other words, invalidation adds a layer of complexity to your application.
  This library tries to help reduce complexity, for instance by
  :ref:`tagging <tags>` cached content. Additionally, if you use Symfony, we
  recommend you use the FOSHttpCacheBundle_.
  which provides additional functionality to make invalidation easier.
* Invalidation is done through requests to your caching proxy. Sending these
  requests could negatively influence performance, in particular if the client
  has to wait for them. This library resolves this issue by optimizing the way
  invalidation requests are sent.

.. _invalidation methods:

Invalidation Methods
--------------------

Cached content can be invalidated in three ways. Not all caching proxies support
all methods, please refer to proxy specific documentation for the details.

.. glossary::

    Purge
        Purge removes content from the caching proxy immediately. The next time a
        client requests the URL, data is fetched from the application, stored in
        the caching proxy, and returned to the client.

        A purge removes all *variants* of the cached content, as per the ``Vary``
        header.

    Refresh
        Fetch the requested page from the backend immediately, even if there would
        normally be a cache hit. The content is not just deleted  from the cache,
        but is replaced with a new version fetched from the application.

        As fetching is done with the parameters of the refresh request, other
        variants of the same content will not be touched.

    Ban
        Unlike purge, ban does not remove the content from the cache
        immediately. Instead, a reference to the content is added to a blacklist (or
        ban list). Every client request is checked against this blacklist. If the
        request happens to match blacklisted content, fresh content is fetched from the
        application, stored in the caching proxy and returned to the client.

        Bans cannot remove content from cache immediately because that would require
        going through all cached content, which could take a long time and reduce
        performance of the cache.

        The ban solution may seem cumbersome, but offers more powerful cache
        invalidation, such as selecting content to be banned by regular expressions.
        This opens the way for powerful invalidation schemes, such as tagging cache
        entries.
