Changelog
=========

See also the [GitHub releases page](https://github.com/FriendsOfSymfony/FOSHttpCache/releases).

2.1.x (unreleased)
------------------

* Upgraded phpunit to 5.7 / 6. If you use anything from the FOS\HttpCache\Test
  namespace you need to update your project to use phpunit 6 (or 5.7, if you
  are using PHP 5.6).
* Added a `TaggableStore` and a `PurgeTagsListener` that adds support for chache
  invalidation based on tags for the Symfony reverse proxy (`HttpCache`). The
  `TaggableStore` also supports pruning the cache directory.

2.0.2
-----

* Support PHP 7.2
  Avoid warning about `count(null)` in PHP 7.2.

2.0.1
-----

### Fixed

* Ban requests now work even when no base URI is configured.

2.0.0
-----

### PHP

* Raised minimum PHP version to 5.6.
* **BC break:** Removed the `Interface` suffix from all interfaces.
* **BC break:** Renamed `HashGenerator` to `DefaultHashGenerator`.
* Added `HashGenerator` interface.

### HTTP

* **BC break:** Replaced hard coupling on Guzzle HTTP client with HTTPlug. 
  You now need to explicitly specify a supported HTTP adapter in composer.json;
  see [installation instructions](http://foshttpcache.readthedocs.io/en/stable/installation.html).
* **BC break:** Separated the HttpDispatcher from the proxy clients. All 
  existing clients still use HTTP to send invalidation requests.
* Added support and documentation for setting a custom TTL specifically for the
  caching proxy.

### Logging

* **BC break:** Renamed the log event listener from `LogSubscriber` to 
  `LogListener`.
  
### Proxy clients

* **BC break**: Renamed the `Ban`, `Purge`, `Refresh` and `Tag` interfaces to 
  `BanCapable`, `PurgeCapable`, `RefreshCapable` and `TagCapable`.

### Tagging

* **BC break:** Moved tag invalidation to `CacheInvalidator`, and renamed 
  `TagHandler` to `ResponseTagger`.
* Abstracting tags by adding new `TagCapable` for ProxyClients.
* Added `strict` option to `ResponseTagger` that throws an exception when empty
  tags are added. By default, empty tags are ignored.
* Added `TagHeaderFormatter` interface that is used within the `ResponseTagger`
  to provide the header name and for formatting the tags header value.

### Varnish

* Varnish configuration are now files that you can directly include from your
  .vcl and call custom functions to avoid copy-pasting VCL code.
* Added support for and changed default to Varnish version 5.  
* Moved Varnish 4 and 5 configuration files from `resources/config/varnish-4/`
  to `resources/config/varnish/`.
* Changed default Varnish version to 5.
* Removed special case for anonymous users in user context behaviour. Varnish
  now does a hash lookup for anonymous users as well.

### NGINX

* The NGINX purge location is no longer passed as constructor argument but by
  calling `setPurgeLocation()`.

### Symfony HttpCache

* **BC break:** Renamed all event listeners to `XxListener` instead of 
  `XxSubscriber`.
* **BC break:** Constructors for `PurgeListener` and `RefreshListener` now use 
  an options array for customization.
* **BC break:** Converted abstract event dispatching kernel class 
  `EventDispatchingHttpCache` to a trait, which now provides the `addSubscriber`
  and `addListener` methods. In your `AppCache`, replace 
  `AppCache extends EventDispatchingHttpInterface` with a 
  `use EventDispatchingHttpCache;` statement. 
* The user context by default does not use a hardcoded hash for anonymous users
  but does a hash lookup. You can still configure a hardcoded hash.  

### Testing

* **BC break:** Refactored the proxy client test system into traits. Removed 
  `ProxyTestCase`; use the traits `CacheAssertions` and `HttpCaller` instead.
* Added HTTP method parameter to `HttpCaller::getResponse()`.

2.0.0-beta3
-----------

* **BC break:** The `ResponseTagger` no longer expects an instance of
  `TagCapable` as first argument. To adjust the tag header name or the way the
  tags are formatted, use the new `header_formatter` option with a
  `TagHeaderFormatter`.

1.4.3
-----

* Avoid warning about `count(null)` in PHP 7.2.

1.4.2
-----

* The TagHandler constructor now accepts a `headerLength` argument which will
  cause its `invalidateTags` function to invalidate in batches if the header
  length exceeds this value.

1.4.1
-----

* Support for Symfony 3.

1.4.0
-----

* Added symfony/http-kernel [HttpCache client](http://foshttpcache.readthedocs.io/en/stable/proxy-clients.html#symfony-client).
* Added [SymfonyTestCase](http://foshttpcache.readthedocs.io/en/stable/testing-your-application.html#symfonytestcase).
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

* Added [TagHandler](http://foshttpcache.readthedocs.io/en/stable/invalidation-handlers.html#tag-handler).
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
