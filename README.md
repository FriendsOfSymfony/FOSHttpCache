FOSHttpCache
============
[![Build Status](https://travis-ci.org/FriendsOfSymfony/FOSHttpCache.png?branch=1.0.0-alpha1)](https://travis-ci.org/FriendsOfSymfony/FOSHttpCache) 
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCache/badges/quality-score.png?s=5b808e92306a54228a81378ec20a47bb5313a5c7)](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCache/)
[![Code Coverage](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCache/badges/coverage.png?s=f9f57d6b28285f38782b38a08b1dbdb24901a764)](https://scrutinizer-ci.com/g/ddeboer/FOSHttpCache/)

Introduction
------------

This library integrates your PHP applications with HTTP caching proxies such as Varnish.
Use this library to send invalidation requests from your application to the caching proxy
and to test your caching and invalidation code against a Varnish setup.

If you use Symfony2, have a look at the
[FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle).
The bundle provides the invalidator as a service, along with a number of
Symfony2-specific features to help with caching and caching proxies.

Features
--------

* Send [cache invalidation requests](doc/cache-invalidator.md) with minimal impact on performance.
* Use the built-in support for [Varnish](doc/varnish.md) or easily implement your own caching proxy client.
* [Test your application](doc/testing-your-application.md) against your Varnish setup.
* This library is compatible with [HHVM](http://www.hhvm.com/blog/).

Documentation
-------------

[Documentation](doc/index.md) is included in the [doc](doc/index.md) directory.

License
-------

This library is released under the MIT license. See the included
[LICENSE](LICENSE) file for more information.
