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

Table of Contents
-----------------

1. [Installation and Getting Started](installation.md)
2. [An Introduction to Cache Invalidation](invalidation-introduction.md)
3. Caching Proxy Configuration
   1. [Varnish Configuration](varnish-configuration.md)
   2. Nginx Configuration
4. [Caching Proxy Clients](proxy-clients.md)
   1. [Varnish client](varnish-client.md)
   2. Nginx client
5. [The Cache Invalidator](cache-invalidator.md)
6. [Cache on User Context](user-context.md)
7. Testing
   1. [Testing Your Application](testing-your-application.md)
   2. [Testing the Library](testing-the-library.md)
