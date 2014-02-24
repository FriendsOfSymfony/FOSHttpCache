Installation
============

This library is available on [Packagist](https://packagist.org/packages/friendsofsymfony/http-cache).
You can install it using [Composer](https://getcomposer.org/):

```bash
$ composer require friendsofsymfony/http-cache:~1.0
```

Getting Started
===============

There are three things you need to do to get started:
1. [configure your caching proxy](#configure-your-caching-proxy)
2. [construct a caching proxy client](#cosntruct-a-caching-proxy-client)
3. [construct the cache invalidator](#construct-the-cache-invalidator).

Configure Your Caching Proxy
----------------------------

You should configure your [caching proxy](caching-proxy.md) to handle
invalidation requests.

Currently, this library only supports Varnish. See the [Varnish](varnish.md)
chapter for configuration instructions.

Construct a Caching Proxy Client
--------------------------------

You now need to construct a client that communicates with your caching proxy
server. The caching proxy client is an instance of
[CacheProxyInterface](../src/Invalidation/CacheProxyInterface.php).

Currently, this library offers one implementation: Varnish.

At minimum, supply an array containing IPs or hostnames of the Varnish
proxy servers that you want to send invalidation requests to:

```php
use FOS\HttpCache\Invalidation\Varnish;

$servers = array('10.0.0.1:6081', '10.0.0.2:6081');
$varnish = new Varnish($servers);
```

This is sufficient for invalidating absolute URLs. If you also wish to
invalidate relative paths, supply the hostname (or base URL) where your website
is available as the second parameter:

```php
$varnish = new Varnish($servers, 'my-cool-app.com');
```

Construct the Cache Invalidator
-------------------------------

It is the [cache invalidator](cache-invalidator.md) that you will probably use
most. Create it by passing the proxy client as an
[adapter](http://en.wikipedia.org/wiki/Adapter_pattern):

```php
use FOS\HttpCache\CacheInvalidator;

$cacheInvalidator = new CacheInvalidator($varnish);
```

You are now ready to start invalidating content.

* If you are new to cache invalidation, you may want to read
  [An Introduction to Cache Invalidation](invalidation-introduction.md) first.
* Continue with the [Cache Invalidator](cache-invaldidator.md) to learn
  how to send invalidation requests.