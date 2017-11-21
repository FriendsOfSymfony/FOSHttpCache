Getting started
===============

Installation
------------

The FOSHttpCache library is available on Packagist_. You can install the library
and its dependencies using Composer_:

.. code-block:: bash

    $ composer require friendsofsymfony/http-cache 

The library relies on HTTPlug_ for sending invalidation requests over HTTP, so
you need to install an HTTPlug-compatible client or adapter first:

.. code-block:: bash

    $ composer require php-http/guzzle6-adapter

You also need a `PSR-7 message implementation`_. If you use Guzzle 6, Guzzle’s
implementation is already included. If you use another client, you need to
install one of the message implementations. Recommended:

.. code-block:: bash

    $ composer require guzzlehttp/psr7

Alternatively:

.. code-block:: bash

    $ composer require zendframework/zend-diactoros

Then install the FOSHttpCache library itself:

.. code-block:: bash

    $ composer require friendsofsymfony/http-cache

.. note::

    This library follows `Semantic Versioning`_.  Except for major versions, we
    aim to not introduce BC breaks in new releases.

Configuration
-------------

There are three things you need to do to get started:

1. :doc:`configure your proxy server <proxy-configuration>`
2. :doc:`set up a client for your proxy server <proxy-clients>`
3. :doc:`set up the cache invalidator <cache-invalidator>`

Overview
--------

This library mainly consists of:

* low-level clients for communicating with a proxy server (Varnish, NGINX and
  Symfony HttpCache)
* a cache invalidator that acts as an abstraction layer for the proxy
  client
* test classes that you can use for integration testing your application
  against a proxy server.

Measures have been taken to minimize the performance impact of sending
invalidation requests:

* Requests are not sent immediately, but aggregated to be sent in parallel.
* You can determine when the requests should be sent. For optimal performance,
  do so after the response has been sent to the client.

.. _Packagist: https://packagist.org/packages/friendsofsymfony/http-cache
.. _Composer: http://getcomposer.org
.. _PSR-7 message implementation: https://packagist.org/providers/psr/http-message-implementation
.. _Semantic Versioning: http://semver.org/
.. _HTTPlug: http://httplug.io
