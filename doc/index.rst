FOSHttpCache
============

This is the documentation for the `FOSHttpCache library <https://github.com/FriendsOfSymfony/FOSHttpCache>`_.

.. note::

    This documentation is for the 3.* version of the library. For the 2.*
    version, please refer to the `2.x documentation`_.

This library integrates your PHP applications with HTTP caching proxies such as
Varnish, NGINX or the Symfony HttpCache class. Use this library to send
invalidation requests from your application to the proxy server and to test
your caching and invalidation setup.

If you use the Symfony full stack framework, have a look at the FOSHttpCacheBundle_.
The bundle provides the Invalidator as a service, support for the built-in cache
kernel of Symfony and a number of Symfony-specific features to help with caching and
proxy servers.

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

.. _2.x documentation: https://foshttpcache.readthedocs.io/en/2.x/
