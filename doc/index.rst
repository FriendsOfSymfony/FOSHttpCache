FOSHttpCache
============

This is the documentation for the `FOSHttpCache library <https://github.com/FriendsOfSymfony/FOSHttpCache>`_.

This library integrates your PHP applications with HTTP caching proxies such as
Varnish. Use this library to send invalidation requests from your application
to the caching proxy and to test your caching and invalidation setup.

If you use Symfony2, have a look at the FOSHttpCacheBundle_. The bundle
provides the Invalidator as a service, along with a number of
Symfony2-specific features to help with caching and caching proxies.

Contents:

.. toctree::
    :maxdepth: 2

    installation
    invalidation-introduction
    proxy-configuration
    proxy-clients
    cache-invalidator
    user-context

    testing-your-application
    testing-the-library
