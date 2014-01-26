Installation
============

This library is available on [Packagist](https://packagist.org/packages/friendsofsymfony/http-cache).
You can install it using Composer:

```bash
$ composer require friendsofsymfony/http-cache:@stable
```

Getting Started
===============

This library mainly provides the CacheManager class. Use this class to collect
cache invalidation requests in your application and call the `flush` method at
the end of requests to have it send the invalidation requests to your server.

Cache Invalidation
------------------

Basically, there are 3 operations:

* Purge: Invalidate a specific URL in all its variants (as specified by the
  VARY header) and with all query strings. In the case of Varnish, this will
  remove the entries from the cache immediately.
* Refresh: Similar to purge: Invalidate a specific URL with all query strings,
  but not its variants. Do an immediate request to the backend to have the page
  in the cache again.
* Ban: This is a way more powerful operation. Remove all requests matching
  specified regular expressions on any desired headers. This can invalidate a
  subset of URLs but also custom headers, as used with the
  CacheManager::invalidateTags method. In the case of Varnish, this will only
  record the fact that cache entries are to be ignored, for performance
  reasons.

All of these methods are explained in detail in the
[Invalidation](invalidation.md) chapter.

Bootstrap
---------

The CacheManager is configured with an instance of CacheProxyInterface which
also implements at least one of PurgeInterface, RefreshInterface, BanInterface.
Using the provided Varnish client, the bootstrap code looks as follows:


```php
use FOS\HttpCache\CacheManager;
use FOS\HttpCache\Invalidation\Varnish;

// IPs varnish is listening on
$ips = array('10.0.0.1:6081', '10.0.0.2:6081');
// hostname to use in requests
$host = 'www.test.com';
$varnish = new Varnish(array $ips, $host);

// to get log messages if invalidation fails unexpectedly, give the client a
// logger instance
$varnish->setLogger(...);

$cacheManager = new CacheManager($varnish);
```

Thats it, you are ready to start caching. Read on in the next chapter about the
[Cache Manager](cache-manager.md). You may also want to know more about the
[Lower-level HTTP proxy classes](http-proxy.md) like the `Varnish` class we
used in this example.
