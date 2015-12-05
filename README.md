FOSHttpCache
============
[![Build Status](https://travis-ci.org/FriendsOfSymfony/FOSHttpCache.svg?branch=master)](https://travis-ci.org/FriendsOfSymfony/FOSHttpCache) 
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/badges/quality-score.png?s=bc263d4deb45becdb1469b71e8630c5e65efdcf4)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/) 
[![Code Coverage](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/badges/coverage.png?s=a19df7bb7e830642fb937891aebe8c3e1c9f59c0)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/)
[![Latest Stable Version](https://poser.pugx.org/friendsofsymfony/http-cache/v/stable.svg)](https://packagist.org/packages/friendsofsymfony/http-cache)

Introduction
------------

This library integrates your PHP applications with HTTP caching proxies such as Varnish.
Use this library to send invalidation requests from your application to the caching proxy
and to test your caching and invalidation code against a Varnish setup.

If you use Symfony, have a look at the
[FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle).
The bundle provides the invalidator as a service, along with a number of
Symfony-specific features to help with caching and caching proxies.

Features
--------

* Send [cache invalidation requests](http://foshttpcache.readthedocs.org/en/latest/cache-invalidator.html)
  with minimal impact on performance.
* Use the built-in support for [Varnish](http://foshttpcache.readthedocs.org/en/latest/varnish-configuration.html)
  3 and 4, [NGINX](http://foshttpcache.readthedocs.org/en/latest/nginx-configuration.html), the 
  [Symfony reverse proxy from the http-kernel component](http://foshttpcache.readthedocs.org/en/latest/symfony-cache-configuration.html)
  or easily implement your own caching proxy client.
* [Test your application](http://foshttpcache.readthedocs.org/en/latest/testing-your-application.html)
  against your Varnish or NGINX setup.
* This library is fully compatible with [HHVM](http://www.hhvm.com).

Documentation
-------------

For more information, see [the documentation](http://foshttpcache.readthedocs.org/en/latest/).

License
-------

This library is released under the MIT license. See the included
[LICENSE](LICENSE) file for more information.
