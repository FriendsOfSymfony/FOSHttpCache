FOSHttpCache Documentation
==========================

This is the documentation for the FOSHttpCache library.

This library integrates your PHP applications with HTTP caching proxies such as
Varnish. Use this library to send invalidation requests from your application
to the caching proxy and to test your caching and invalidation code against a
Varnish setup.

If you use Symfony2, have a look at the
[FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle).
The bundle provides the invalidator as a service, along with a number of
Symfony2-specific features to help with caching and caching proxies.

This documentation covers:

1. [Installation and Getting Started](installation.md)
2. [An Introduction to Cache Invalidation](invalidation-introduction.md)
2. [The Cache Invalidator](cache-invalidator.md)
3. [Caching Proxy Clients](caching-proxy.md)
   1. [Interfaces](interfaces.md)
   2. [Varnish Configuration](varnish-configuration.md)
   3. [Nginx](nginx.md)
4. Testing
   1. [Testing Your Application](testing-your-application.md)
   2. [Testing the Library](testing-the-library.md)