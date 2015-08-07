Changelog
=========

See also the [GitHub releases page](https://github.com/FriendsOfSymfony/FOSHttpCache/releases).

2.0.0 (unreleased)
------------------

* Replace hard coupling on Guzzle HTTP client with HTTP adapter.
* The NGINX purge location is no longer passed as constructor argument but by
  calling `setPurgeLocation()`.
* In ProxyTestCase, `getHttpClient()` has been replaced with `getHttpAdapter()`;
  added HTTP method parameter to `getResponse()`.
* Changed default Varnish version to 4.
* Added support and documentation for setting a custom TTL specifically for the
  caching proxy.
* Refactored the proxy client test system into traits. Removed ProxyTestCase,
  use the traits `CacheAssertions` and `HttpCaller` instead.

1.4.0
-----

* Added symfony/http-kernel [HttpCache client](http://foshttpcache.readthedocs.org/en/stable/proxy-clients.html#symfony-client).
* Added [SymfonyTestCase](http://foshttpcache.readthedocs.org/en/stable/testing-your-application.html#symfonytestcase).
* Removed unneeded files from dist packages.

1.3.2
-----

* Added `TagHandler->hasTags()` method.

1.3.1
-----

* Added authentication support to user context subscribe.
* Fixed usage of deprecated Guzzle subtree splits.
* Fixed exposed cache tags.

1.3.0
-----

* Added [TagHandler](http://foshttpcache.readthedocs.org/en/stable/invalidation-handlers.html#tag-handler).
* It is no longer possible to change the event dispatcher of the
  CacheInvalidator once its instantiated. If you need a custom dispatcher, set
  it right after creating the invalidator instance.
* Deprecated `CacheInvalidator::addSubscriber` in favor of either using the event
  dispatcher instance you inject or doing `getEventDispatcher()->addSubscriber($subscriber)`.

1.2.0
-----

* Added support for the symfony/http-kernel component reverse proxy HttpCache.

1.1.2
-----

* Fixed documentation for user context varnish configuration to also work when
  client omits the `Accept` HTTP header.
