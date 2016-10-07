Changelog
=========

See also the [GitHub releases page](https://github.com/FriendsOfSymfony/FOSHttpCache/releases).

2.0.0 (unreleased)
------------------

### Proxy Clients

* Abstracted proxy clients from HTTP protocol. All existing clients still use
  HTTP to send invalidation requests.

### HTTP

* Replaced hard coupling on Guzzle HTTP client with HTTP adapter. You now need
  to explicitly specify the adapter you want, see [installation instructions]
  (http://foshttpcache.readthedocs.org/en/stable/installation.html)
* Added support and documentation for setting a custom TTL specifically for the
  caching proxy.

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
* Changed default Varnish version to 4.

### NGINX

* The NGINX purge location is no longer passed as constructor argument but by
  calling `setPurgeLocation()`.

### Symfony HttpCache

* BC BREAK: Constructors for PurgeSubscriber and RefreshSubscriber now use an
  options array for customization.

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
