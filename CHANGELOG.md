Changelog
=========

See also the [GitHub releases page](https://github.com/FriendsOfSymfony/FOSHttpCache/releases).

2.0.0
-----

### PHP

* Raised minimum PHP version to 5.5.
* **BC break:** Removed the `Interface` suffix from all interfaces.
* **BC break:** Renamed ``HashGenerator`` to ``DefaultHashGenerator``.
* Added interface ``HashGenerator``

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
* Abstracting tags by adding new `TagsInterface` for ProxyClients.
* Added `strict` option to `ResponseTagger` that throws an exception when empty
  tags are added. By default, empty tags are ignored.

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

1.4.2
-----

* The TagHandler constructor now accepts a ``headerLength`` argument which will
  cause its ``invalidateTags`` function to invalidate in batches if the header
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
