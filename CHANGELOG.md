Changelog
=========

See also the [GitHub releases page](https://github.com/FriendsOfSymfony/FOSHttpCache/releases).

2.15.1
------

* Directly require `php-http/message-factory` to keep working with the legacy factories.

2.15.0
------

* Provide a `TagHeaderParser` that can split up a tag header into the list of tags.
  This allows to correctly handle non-default tag separators in all places.

2.14.2
------

* Fixed varnish configuration examples to say `Authorization` and not `Autorization`.

2.14.1
------

* Fixed dummy interface name for code scanners to match the expected name.

2.14.0
------

### Symfony Compatibility

* Removed the internal BaseEvent class and extend our events from the Symfony contracts class directly.
* Made the CacheInvalidation interface compatible with Symfony 6.
  Symfony 6 does a BC break for `HttpKernelInterface::fetch` - if you implement the method in your application,
  you need to adjust when upgrading to Symfony 6.

2.13.0
------

* Allow installation with Symfony 6 components
* Drop support for Symfony 3, minimal version is now 4.3

2.12.1
------

### Symfony Compatibility

* Do not extend the wrong Event class when installed with legacy Symfony but the Symfony contracts happen to be installed

2.12.0
------

### Cloudflare

* Added Cloudflare ProxyClient Adapter with ClearCapable, PurgeCapable and
  TagCapable. This allows to use FOSHttpCache to invalidate caches on
  Cloudflare. See the "Proxy Client" section of the documentation for how to
  configure the Cloudflare client.

### Varnish Cache

* Added a `fos_user_context_hash` method to be called in `vcl_hash` when using the user context
  hash mechanism. This can avoid performance problems Varnish can run into when the hash `Vary`s on
  the basic authentication or session cookie.
  If you use the user context, read the updated documentation and call `fos_user_context_hash` in
  your `vcl_hash` function.

### Noop Client

* Implemented ClearCapable.

2.11.0
------

### Symfony HttpCache

* Added flag on CustomTtlListener to allow keeping the custom TTL header on the response

### Fastly client

- Marked the `@internal` constants in `FOS\HttpCache\ProxyClient\Fastly` as private

2.10.1
------

### HttpDispatcher

- Fix how we create exceptions to get the expected exception rather than a PHP error

2.10.0
------

### General

* Raised minimal PHP version to 7.2
* Added support for PHP 8.0

### Symfony HttpCache

* Added a neutral "Bad Request" body to user hash mismatch response to have
  something searchable in the code when debugging.

2.9.2
-----

### Proxy Client

* Fix: Preserve inline credentials when multiplexing to the proxy servers.

2.9.1
-----

### Proxy Client

* Allow to use inline credentials for the caching proxy URL in the format http://user:pass@1.2.3.4

2.9.0
-----

### General

* Raised minimal PHP version to 7.1
* Test helper classes upgraded to support PHPUnit 7/8
* There is no php-http/discovery package 2.0, drop it from composer.json
* Adapt for changes to NetworkException in HTTPlug 2.1 for PSR-18 compatibility

### Symfony HttpCache

* Fixed issue with `PurgeTagsListener` and Symfony 5
* Fixed clearing the cache completely together with toflar psr6 store did not work

2.8.0
-----

### General

* Support Symfony 5
* Use `LegacyEventDispatcherProxy` for Symfony >= 4.3 to avoid deprecation messages.
* Added Fastly ProxyClient Adapter with ClearCapable, PurgeCapable, RefreshCapable, & TagCapable.
  Fastly is a CDN originally based on Varnish 2.x, so with many of the same capabilities like VCL and more.

2.7.0
-----

### Symfony HttpCache

* Added request type to the CacheEvent.

2.6.0
-----

### General

* Added: ClearCapable to clear the whole cache in one efficient call. Currently
  supported only by the Symfony HttpCache.
* Allow installing with Httplug 2.0 / PSR-18.

2.5.4
-----

### Symfony HttpCache

* Fixed: Avoid regression of 2.5.3: If there are no messages to be dispatched,
  do not throw an exception if the HttpCache is not set.

2.5.3
-----

### Symfony HttpCache

* Fixed: Handle HttpCache not available in KernelDispatcher and fix return
  type annotations - if HttpCache is not set, it can't be returned.

2.5.2
-----

### Varnish

* Fixed: Remove the xkey header in vcl_deliver if we are not in debug mode
* Do not cleanup the Vary header and keep the user context hash if we are in debug mode

### Cache Tagging

* Fixed: Clear the ResponseTagger after we tagged a response. Usually PHP uses
  a new instance for every request. But for example the hash lookup when using
  Symfony HttpCache does two requests in the same PHP process.

2.5.1
-----

### Cache Tagging

* Fixed: `MaxHeaderValueLengthFormatter::getTagsHeaderName` now actually returns the value.

2.5.0
-----

### Cache Tagging

* Added: `MaxHeaderValueLengthFormatter` to allow splitting cache tag headers into
  multiple headers.

### Symfony HttpCache

* Have cache invalidator check for presence of Psr6Store for a better guess
  whether the cache really is TagCapable or not.

2.4.0
-----

### Symfony HttpCache

* Added: `CleanupCacheTagsListener` to remove the cache tags header from the final
  response that is sent to the client. Add this listener to your cache kernel.

### Cache Tagging

* Improved: The `ResponseTagger` does now remove duplicate tags.

2.3.1
-----

### Varnish

* Fixed: Do not `preg_quote` tags when using xkey. Quoting is only used for BAN
  requests that expect a regular expression. This bug only affected you if you
  use xkey *and* used characters in your tags that are changed by `preg_quote`.

2.3.0
-----

### ProxyClient

* The `HttpProxyClient` now accepts an instance of the new `Dispatcher` interface
  instead of the concrete `HttpDispatcher`, allowing for more flexibility.

### Symfony

* Introduced a new `KernelDispatcher` for the Symfony proxy that calls the application
  kernel directly instead of executing a full HTTP request.

2.2.1
-----

### Varnish

* The provided VCL for custom TTL no longer provides `import std;` because each
  import may only exist once.

2.2.0
-----

### Varnish

* Added support for the more efficient xkey cache tag system. BAN remains the
  default cache tagging system, but if you can install the varnish modules in
  your system, it is recommended to update to xkey.
* No inline C is needed for the custom TTL header with Varnish 4 or better. use
  `std.duration()` instead.

### Symfony user context
* You can now also specify which headers are used for
  authentication to detect anonymous requests. By default, the headers are the
  previously hardcoded `Authorization`, `HTTP_AUTHORIZATION` and
  `PHP_AUTH_USER`.

2.1.3
-----

#### Symfony HttpCache

* Fixed bug in Symfony tag invalidation.
  Do not check if host is missing in request creation.

2.1.2
-----

### Symfony HttpCache

* Fixed issue with detection if toflar psr6 store is available.

2.1.1
-----

### Varnish

* Updated X-Cache-Tags regex to prevent matching partial tags

  Invalidating objects with a tag of 'bar' would have previously also have
  invalidated objects with a tag that ends in 'bar', eg. 'foobar'. Now when
  invalidating an object the tag name must match in full.

2.1.0
-----

* Support Symfony 4.

### Testing

* Upgraded phpunit to 5.7 / 6. If you use anything from the
  `FOS\HttpCache\Test` namespace you need to update your project to use
  PHPUnit 6 (or 5.7, if you are using PHP 5.6).

### Symfony HttpCache

* Cache tagging support for Symfony HttpCache

  Added a `PurgeTagsListener` for tag based invalidation with the Symfony
  `HttpCache` reverse caching proxy. This requires the newly created
  [Toflar Psr6Store](https://github.com/Toflar/psr6-symfony-http-cache-store)
  built on PSR-6 cache and supporting pruning expired cache entries.
* Using Request::isMethodCacheable rather than Request::isMethodSafe to
  correctly handle OPTIONS and TRACE requests.

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

1.4.6
-----

* [Symfony HttpCache] Added a neutral "Bad Request" body to user hash mismatch
  response to have something searchable in the code when debugging.

1.4.5
-----

* Symfony user context: You can now also specify which headers are used for
  authentication to detect anonymous requests. By default, the headers are the
  previously hardcoded `Authorization`, `HTTP_AUTHORIZATION` and
  `PHP_AUTH_USER`.

1.4.4
-----

* Avoid problem with [http_method_override](http://symfony.com/doc/current/reference/configuration/framework.html#configuration-framework-http-method-override).

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
