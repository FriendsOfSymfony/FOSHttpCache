Changelog
=========
1.4.2
-----

* The TagHandler constructor now accepts a ``headerLenght`` argument which will
  cause it's ``invalidateTags`` function to invalidate in batches if the header
  length exceeds this value.

1.3.3
-----

* **2015-05-08** Added a client for the Symfony built-in HttpCache

1.3.0
-----

* Added [TagHandler](http://foshttpcache.readthedocs.org/en/latest/invalidation-handlers.html#tag-handler).
* It is no longer possible to change the event dispatcher of the
  CacheInvalidator once its instantiated. If you need a custom dispatcher, set
  it right after creating the invalidator instance.
* Deprecated `CacheInvalidator::addSubscriber` in favor of either using the event
  dispatcher instance you inject or doing `getEventDispatcher()->addSubscriber($subscriber)`.

1.2.0
-----

* **2014-12-05** Added support for the symfony/http-kernel component reverse proxy HttpCache.

1.1.2
-----

* **2014-11-17** Fixed documentation for user context varnish configuration to also work when
  client omits the `Accept` HTTP header.
