Getting started
===============

Installation
------------

The FOSHttpCache library is available on Packagist_. You can install it using
Composer_:

.. code-block:: bash

    $ composer require friendsofsymfony/http-cache

Note that the library needs a ``psr/http-message-implementation`` and
``php-http/adapter-implementation``. If your project does not contain one,
composer will complain that it did not find ``psr/http-message-implementation``.

When on PHP 5.5+, use the following line instead:

.. code-block:: bash

    $ composer require friendsofsymfony/http-cache guzzlehttp/psr7:^1.0 php-http/guzzle6-adapter:^0.1.0

On PHP 5.4, the ``php-http/guzzle5-adapter:^0.1.0`` works fine.

.. note::

    This library follows `Semantic Versioning`_.  Except for major versions, we
    aim to not introduce BC breaks in new releases. You should still test your
    application after upgrading though. What is a bug fix for somebody could
    break something for others when they where (probably unawares) relying on
    that bug.

Configuration
-------------

There are three things you need to do to get started:

1. :doc:`configure your caching proxy <proxy-configuration>`
2. :doc:`set up a client for your caching proxy <proxy-clients>`
3. :doc:`set up the cache invalidator <cache-invalidator>`

Overview
--------

This library mainly consists of:

* low-level clients for communicating with caching proxies (Varnish and NGINX)
* a cache invalidator that acts as an abstraction layer for the caching proxy
  clients
* test classes that you can use for integration testing your application
  against a caching proxy.

Measures have been taken to minimize the performance impact of sending
invalidation requests:

* Requests are not sent immediately, but aggregated to be sent in parallel.
* You can determine when the requests should be sent. For optimal performance,
  do so after the response has been sent to the client.

.. _Packagist: https://packagist.org/packages/friendsofsymfony/http-cache
.. _Composer: http://getcomposer.org
.. _Semantic Versioning: http://semver.org/
