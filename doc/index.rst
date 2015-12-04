FOSHttpCache
============

This is the documentation for the `FOSHttpCache library <https://github.com/FriendsOfSymfony/FOSHttpCache>`_.

.. note::

    This documentation is for the (upcoming) 2.0 of the library. For the stable
    1.* version, please refer to the `stable documentation`_.

This library integrates your PHP applications with HTTP caching proxies such as
Varnish, NGINX or the Symfony HttpCache class. Use this library to send
invalidation requests from your application to the caching proxy and to test
your caching and invalidation setup.

If you use the Symfony2 full stack framework, have a look at the FOSHttpCacheBundle_.
The bundle provides the Invalidator as a service, support for the built-in cache
kernel of Symfony and a number of Symfony2-specific features to help with caching and
caching proxies.

Contents:

.. toctree::
    :maxdepth: 2

    installation
    invalidation-introduction
    proxy-configuration
    proxy-clients
    cache-invalidator
    response-tagging
    user-context

    testing-your-application
    contributing

.. _stable documentation: http://foshttpcache.readthedocs.org/en/stable/
