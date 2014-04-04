The Cache Invalidator
=====================

Use the cache invalidator to invalidate or refresh paths, URLs and headers.
It is the invalidator that you will probably use most when interacting with
the library. 

* [Setup](#setup)
* [Invalidating Paths and URLs](#invalidating-paths-and-urls)
* [Refreshing Paths and URLs](#refreshing-paths-and-urls)
* [Invalidating with a Regular Expression](#invalidating-with-a-regular-expression)
  * [URL, Content Type and Hostname](#urls-content-type-and-hostname)
  * [Any Header](#any-header)
* [Tags](#tags)
  * [Custom Tags Header](#custom-tags-header)
* [Flushing](#flushing)
* [Error handling](#error-handling)
  * [Logging errors](#logging-errors)

Setup
-----

Create the cache invalidator by passing a [proxy client](proxy-clients.md) as
an [adapter](http://en.wikipedia.org/wiki/Adapter_pattern):

```php
use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\ProxyClient;

$client = new ProxyClient\Varnish();
// or
$client = new ProxyClient\Nginx();

$cacheInvalidator = new CacheInvalidator($client);
```

Invalidating Paths and URLs
---------------------------

Make sure to [configure your proxy](proxy-configuration.md) for purging first.

Invalidate a path:

```php
$cacheInvalidator->invalidatePath('/users')
    ->flush()
;
```

See below for the [flush](#flushing) method.

Invalidate a URL:

```php
$cacheInvalidator->invalidatePath('http://www.example.com/users')->flush();
```

Refreshing Paths and URLs
-------------------------

Make sure to [configure your proxy](proxy-configuration.md) for refreshing
first.

Refresh a path:

```php
$cacheInvalidator->refreshPath('/users')->flush();
```

Refresh a URL:

```php
$cacheInvalidator->refreshPath('http://www.example.com/users')->flush();
```

Invalidating With a Regular Expression
--------------------------------------

Make sure to [configure your proxy](proxy-configuration.md) for banning first.

### URL, Content Type and Hostname

You can invalidate all URLs matching a regular expression by using the
`invalidateRegex` method. You can further limit the cache entries to invalidate
with a regular expression for the content type and/or the application hostname.

For instance, to invalidate all .css files for all hostnames handled by this
caching proxy:

```php
$cacheInvalidator->invalidateRegex('.*css$')->flush();
```

To invalidate all .png files for host example.com:

```php
$cacheInvalidator
    ->invalidateRegex('.*', 'image/png', array('example.com'))
    ->flush()
;
```

### Any Header

You can also invalidate the cache based on any headers. If you use non-default
headers, make sure to [configure your proxy accordingly](proxy-configuration.md)
to have them taken into account.

Cache client implementations should fill up the headers to at least have the
default headers always present to simplify the cache configuration rules.

To invalidate on a custom header X-My-Header, you would do:

```php
$cacheInvalidator->invalidate(array('X-My-Header' => 'my-value'))->flush();
```

Tags
----

Make sure to [configure your proxy for tagging](proxy-configuration.md) first.

You will have to make sure your web application adds the correct tags on all
responses by setting the `X-Cache-Tags` header. The
[FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle)
does this for you when you’re using Symfony.

Assume you sent four responses:

* `/one` had the header `X-Cache-Tags: tag-one`
* `/two` had the header `X-Cache-Tags: tag-two, group-a`
* `/three` had the header `X-Cache-Tags: tag-three, group-a`
* `/four` had the header `X-Cache-Tags: tag-four, group-b`

You can now invalidate some URLs using tags:

```php
$cacheInvalidator->invalidateTags(array('group-a', 'tag-four'))->flush();
```

This will ban all requests having either the tag `group-a` /or/ `tag-four`. In
the above example, this will invalidate `/two`, `/three` and `/four`. Only `/one`
will stay in the cache.

### Custom Tags Header

Tagging uses a custom HTTP header to identify tags. You can change the default
header `X-Cache-Tags` by calling `setTagsHeader()`. Make sure to reflect this
change in your [caching proxy configuration](varnish-configuration.md#tagging).

Flushing
--------

The CacheInvalidator internally queues the invalidation requests and only sends
them out to your HTTP proxy when you call `flush()`:

```php
$cacheInvalidator
    ->invalidateRoute(...)
    ->invalidatePath(...)
    ->flush()
;
```

Try delaying flush until after the response has been sent to the client’s
browser. This keeps the performance impact of sending invalidation requests to
a minimum.

When using the [Symfony bundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle),
you don’t have to call `flush()`, as the bundle flushes the invalidator for you
after the response has been sent.

As `flush()` empties the invalidation queue, you can safely call the method 
multiple times.

Error handling
--------------

If an error occurs during `flush()`, the method throws an
[ExceptionCollection](../src/Exception/ExceptionCollection.php) that contains
an exception for each failed request to the caching proxy.

These exception are of two types:
* `\FOS\HttpCache\ProxyUnreachableException` when the client cannot connect to
   the caching proxy
* `\FOS\HttpCache\ProxyResponseException` when the caching proxy returns an
   error response, such as 403 Forbidden.

So, to catch exceptions:

```php
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
```

### Logging errors

You can log any exceptions in the following way. First construct a logger that
implements `\Psr\Log\LoggerInterface`. For instance, when using
[Monolog](https://github.com/Seldaek/monolog):

```php
use Monolog\Logger;

$monolog = new Logger(...);
$monolog->pushHandler(...);
```

Then add the logger as a subscriber to the cache invalidator:

```php
use FOS\HttpCache\EventListener\LogSubscriber;

$subscriber = new LogSubscriber($monolog);
$cacheInvalidator->addSubscriber($subscriber);
```

Now, if you flush the invalidator, errors will be logged:

```php
use FOS\HttpCache\Exception\ExceptionCollection;

$cacheInvalidator->invalidatePath(...)
    ->invalidatePath(...);

try {
    $cacheInvalidator->flush();
} catch (ExceptionCollection $exceptions) {
    // At least one failed request, check your logs!
}
```
