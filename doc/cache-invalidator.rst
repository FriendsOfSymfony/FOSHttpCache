The Cache Invalidator
=====================

Use the cache invalidator to invalidate or refresh paths, URLs and headers.
It is the invalidator that you will probably use most when interacting with
the library.

Setup
-----

Create the cache invalidator by passing a proxy client as
`adapter <http://en.wikipedia.org/wiki/Adapter_pattern>`_::

    use FOS\HttpCache\CacheInvalidator;
    use FOS\HttpCache\ProxyClient;

    $client = new ProxyClient\Varnish(...);
    // or
    $client = new ProxyClient\Nginx(...);
    // or
    $client = new ProxyClient\Symfony(...);

    $cacheInvalidator = new CacheInvalidator($client);

.. note::

    See :ref:`proxy client setup <client setup>` for more on constructing a client.

Invalidating Paths and URLs
---------------------------

.. note::

    Make sure to :doc:`configure your proxy <proxy-configuration>` for purging
    first.

Invalidate a path::

    $cacheInvalidator->invalidatePath('/users')
        ->flush()
    ;

See below for the :ref:`flush() <flush>` method.

Invalidate a URL::

    $cacheInvalidator->invalidatePath('http://www.example.com/users')->flush();

Invalidate a URL with added header(s)::

    $cacheInvalidator->invalidatePath(
        'http://www.example.com/users',
        array('Cookie' => 'foo=bar; fizz=bang')
    )->flush();

.. include:: includes/custom-headers.rst

Refreshing Paths and URLs
-------------------------

.. note::

    Make sure to :doc:`configure your proxy <proxy-configuration>` for refreshing
    first.

.. code-block:: php

    $cacheInvalidator->refreshPath('/users')->flush();

Refresh a URL::

    $cacheInvalidator->refreshPath('http://www.example.com/users')->flush();

Refresh a URL with added header(s)::

    $cacheInvalidator->refreshPath(
        'http://www.example.com/users',
        array('Cookie' => 'foo=bar; fizz=bang')
    )->flush();

.. include:: includes/custom-headers.rst

.. _invalidate regex:

Invalidating With a Regular Expression
--------------------------------------

.. note::

    Make sure to :doc:`configure your proxy <proxy-configuration>` for banning
    first.

URL, Content Type and Hostname
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can invalidate all URLs matching a regular expression by using the
``invalidateRegex`` method. You can further limit the cache entries to invalidate
with a regular expression for the content type and/or the application hostname.

For instance, to invalidate all .css files for all hostnames handled by this
caching proxy::

    $cacheInvalidator->invalidateRegex('.*css$')->flush();

To invalidate all ``.png`` files on host example.com::

    $cacheInvalidator
        ->invalidateRegex('.*', 'image/png', array('example.com'))
        ->flush()
    ;

Any Header
~~~~~~~~~~

You can also invalidate the cache based on any headers.

.. note::

    If you use non-default headers, make sure to :doc:`configure your proxy <proxy-configuration>`
    to have them taken into account.

Cache client implementations should fill up the headers to at least have the
default headers always present to simplify the cache configuration rules.

To invalidate on a custom header ``X-My-Header``, you would do::

    $cacheInvalidator->invalidate(array('X-My-Header' => 'my-value'))->flush();

.. _flush:

Flushing
--------

The CacheInvalidator internally queues the invalidation requests and only sends
them out to your HTTP proxy when you call ``flush()``::

    $cacheInvalidator
        ->invalidateRoute(...)
        ->invalidatePath(...)
        ->flush()
    ;

Try delaying flush until after the response has been sent to the client’s
browser. This keeps the performance impact of sending invalidation requests to
a minimum.

When using the FOSHttpCacheBundle_, you don’t have to call ``flush()``, as the
bundle flushes the invalidator for you after the response has been sent.

As ``flush()`` empties the invalidation queue, you can safely call the method
multiple times.

Error handling
--------------

If an error occurs during ``flush()``, the method throws an
:source:`ExceptionCollection <src/Exception/ExceptionCollection.php>`
that contains an exception for each failed request to the caching proxy.

These exception are of two types:

* ``\FOS\HttpCache\ProxyUnreachableException`` when the client cannot connect to
  the caching proxy
* ``\FOS\HttpCache\ProxyResponseException`` when the caching proxy returns an
  error response, such as 403 Forbidden.

So, to catch exceptions::

    use FOS\HttpCache\Exception\ExceptionCollection;

    $cacheInvalidator
        ->invalidatePath('/users');

    try {
        $cacheInvalidator->flush();
    } catch (ExceptionCollection $exceptions) {
        // The first exception that occurred
        var_dump($exceptions->getFirst());

        // Iterate over the exception collection
        foreach ($exceptions as $exception) {
            var_dump($exception);
        }
    }

Logging errors
~~~~~~~~~~~~~~

You can log any exceptions in the following way. First construct a logger that
implements ``\Psr\Log\LoggerInterface``. For instance, when using Monolog_::

    use Monolog\Logger;

    $monolog = new Logger(...);
    $monolog->pushHandler(...);

Then add the logger as a subscriber to the cache invalidator::

    use FOS\HttpCache\EventListener\LogSubscriber;

    $subscriber = new LogSubscriber($monolog);
    $cacheInvalidator->getEventDispatcher()->addSubscriber($subscriber);

Now, if you flush the invalidator, errors will be logged::

    use FOS\HttpCache\Exception\ExceptionCollection;

    $cacheInvalidator->invalidatePath(...)
        ->invalidatePath(...);

    try {
        $cacheInvalidator->flush();
    } catch (ExceptionCollection $exceptions) {
        // At least one failed request, check your logs!
    }

.. _Monolog: https://github.com/Seldaek/monolog
