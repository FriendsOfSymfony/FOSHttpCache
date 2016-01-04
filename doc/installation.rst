Getting started
===============

Installation
------------

The FOSHttpCache library is available on Packagist_. You can install it using
Composer_.

The library relies on HTTPlug_ for sending invalidation requests over HTTP, so
you need to install an HTTPlug-compatible client or adapter first:

.. code-block:: bash

    $ composer require php-http/guzzle6-adapter

Then install the FOSHttpCache library itself:

.. code-block:: bash

    $ composer require friendsofsymfony/http-cache

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
.. _HTTPlug: http://httplug.io
