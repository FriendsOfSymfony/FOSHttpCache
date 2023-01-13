FOSHttpCache
============
[![CI](https://github.com/FriendsOfSymfony/FOSHttpCache/actions/workflows/ci.yml/badge.svg)](https://github.com/FriendsOfSymfony/FOSHttpCache/actions/workflows/ci.yml)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/badges/quality-score.png?s=bc263d4deb45becdb1469b71e8630c5e65efdcf4)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/)
[![Code Coverage](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/badges/coverage.png?s=a19df7bb7e830642fb937891aebe8c3e1c9f59c0)](https://scrutinizer-ci.com/g/FriendsOfSymfony/FOSHttpCache/)
[![Latest Stable Version](https://poser.pugx.org/friendsofsymfony/http-cache/v/stable.svg)](https://packagist.org/packages/friendsofsymfony/http-cache)
[![Documentation Status](https://readthedocs.org/projects/foshttpcache/badge/?version=latest)](https://foshttpcache.readthedocs.io/)

Introduction
------------

This library integrates your PHP applications with HTTP caching proxies such as Varnish.
Use this library to send invalidation requests from your application to the caching proxy
and to test your caching and invalidation code against a Varnish setup.

It does this by abstracting some caching concepts and attempting to make sure these
can be supported across Varnish, Nginx and Symfony HttpCache.

If you use Symfony, have a look at the
[FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle).
The bundle provides the invalidator as a service, along with a number of
Symfony-specific features to help with caching and caching proxies.

Features
--------

* Send [cache invalidation requests](http://foshttpcache.readthedocs.io/en/stable/cache-invalidator.html)
  with minimal impact on performance.
* Cache tagging abstraction for caching proxy systems that support it.
* Use the built-in support for [Varnish](http://foshttpcache.readthedocs.io/en/stable/varnish-configuration.html),
  [NGINX](http://foshttpcache.readthedocs.io/en/stable/nginx-configuration.html), the
  [Symfony reverse proxy from the http-kernel component](http://foshttpcache.readthedocs.io/en/stable/symfony-cache-configuration.html),
  the CDNs [fastly](https://www.fastly.com/) and [cloudflare](https://www.cloudflare.com/),
  or implement your own caching proxy client.
* [Test your application](http://foshttpcache.readthedocs.io/en/stable/testing-your-application.html)
  against your Varnish or NGINX setup with the help of a PHPUnit functional test case.

Documentation
-------------

For more information, see [the documentation](http://foshttpcache.readthedocs.io/en/stable/).

License
-------

This library is released under the MIT license. See the included
[LICENSE](LICENSE) file for more information.
