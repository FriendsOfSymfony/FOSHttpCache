Changelog
=========

See also the [GitHub releases page](https://github.com/FriendsOfSymfony/FOSHttpCache/releases).

2.0.0 (unreleased)
------------------

### HTTP

* Replaced hard coupling on Guzzle HTTP client with HTTPlug. You now need
  to explicitly specify a supported HTTP adapter in composer.json, see [installation instructions]
  (http://foshttpcache.readthedocs.org/en/stable/installation.html)
* BC Break: Separated the HttpDispatcher from the proxy clients. All existing
  clients still use HTTP to send invalidation requests.
* Added support and documentation for setting a custom TTL specifically for the
  caching proxy.

### Logging

* BC BREAK: Renamed the log event listener from Logsubscriber to LogListener.

### Tagging

* Abstracting tags by adding new `TagsInterface` for ProxyClients, as part of
  that also:
  BC break: Moved tag invalidation to `CacheInvalidator`, and rename TagHandler
  to ResponseTagger.
* The ResponseTagger validates that no tags are empty. It can skip empty tags
  or throw exceptions

### Varnish

* Varnish configuration are now files that you can directly include from your
  .vcl and call custom functions to avoid copy-pasting VCL code.
* Moved Varnish 4 and 5 configuration files from `resources/config/varnish-4/`
  to `resources/config/varnish/`.
* Changed default Varnish version to 5.

### NGINX

* The NGINX purge location is no longer passed as constructor argument but by
  calling `setPurgeLocation()`.

### Symfony HttpCache

* BC BREAK: Renamed all event listeners to XxListener instead of XxSubscriber.
* BC BREAK: Constructors for PurgeListener and RefreshListener now use an
  options array for customization.
* Provide a trait for the event dispatching kernel, instead of a base class.
  The trait offers both the addSubscriber and the addListener methods.

### Testing

* In ProxyTestCase, `getHttpClient()` has been replaced with `getHttpAdapter()`;
  added HTTP method parameter to `getResponse()`.
* Refactored the proxy client test system into traits. Removed ProxyTestCase,
  use the traits `CacheAssertions` and `HttpCaller` instead.

1.4.2
-----

* The TagHandler constructor now accepts a ``headerLenght`` argument which will
  cause it's ``invalidateTags`` function to invalidate in batches if the header
  length exceeds this value.

1.4.1
-----

* Support for Symfony 3.

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
