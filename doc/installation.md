Getting started
===============

Installation
------------

The FOSHttpCache library is available on [Packagist](https://packagist.org/packages/friendsofsymfony/http-cache).
You can install it using [Composer](https://getcomposer.org/):

```bash
$ composer require friendsofsymfony/http-cache:~1.0
```

Configuration
-------------

There are three things you need to do to get started:
1. [configure your caching proxy](proxy-configuration.md)
2. [set up a client for your caching proxy](proxy-clients.md)
3. [set up the cache invalidator](cache-invalidator).

Overview
--------

This library mainly consists of:
* low-level clients for communicating with caching proxies (Varnish and Nginx)
* a cache invalidator that acts as an abstraction layer for the caching proxy
  clients
* test classes that you can use for integration testing your application
  against a caching proxy.

Measures have been taken to minimise the performance impact of sending
invalidation requests:
* Requests are not sent immediately, but aggregated to be sent in parallel.
* You can determine when the requests should be sent. For optimal performance,
  do so after the response has been sent to the client.

Continue Reading
----------------

* If you are new to cache invalidation, you may want to read
  [An Introduction to Cache Invalidation](invalidation-introduction.md) first.
* Continue with the [Proxy Configuration](proxy-configuration.md) chapter to
  learn how to configure your caching proxy to work with this library.