The Cache Invalidator
=====================

Use the cache invalidator to explicitly invalidate or refresh paths, URLs or
headers.

* [Invalidating Paths and URLs](#invalidating-paths-and-urls)
* [Refreshing Paths and URLs](#refreshing-paths-and-urls)
* [Invalidating with a Regular Expression](#invalidating-with-a-regular-expression)
  * [URL, Content Type and Hostname](#urls-content-type-and-hostname)
  * [Any Header](#any-header)
* [Tags](#tags)
  * [Changing the Tags Header](#changing-the-tags-header)
* [Flushing](#flushing)
* [Error handling](#error-handling)
  * [Logging errors](#logging-errors)

Invalidating Paths and URLs
---------------------------

Make sure to configure your proxy for purging first.
(See [varnish](varnish.md#purge).)

Invalidate a path:

```php
$cacheInvalidator->invalidatePath('/users')
    ->flush()
;
```

See below for the `[flush()](#flushing)` method.

Invalidate an URL:
```php
$cacheInvalidator->invalidatePath('http://www.example.com/users');
```

Refreshing Paths and URLs
-------------------------

Make sure to configure your proxy for refreshing first.
(See [varnish](varnish.md#refresh).)

Refresh a path:

```php
$cacheInvalidator->refreshPath('/users');
```

Refresh an URL:

```php
$cacheInvalidator->refreshPath('http://www.example.com/users');
```

Invalidating With a Regular Expression
--------------------------------------

Make sure to configure your proxy for regular expressions first.
(See [varnish ban](varnish.md#ban).)

### URL, Content Type and Hostname

You can invalidate all URLs matching a regular expression by using the
`invalidateRegex` method. You can further limit the cache entries to invalidate
with a regular expression for the content type and/or the hostname.

For instance, to invalidate all .css files for all hostnames handled by this
caching proxy:

```php
$cacheInvalidator->invalidateRegex('.*css$');
```

To invalidate all .png files for host example.com:

```php
$cacheInvalidator->invalidateRegex('.*', 'image/png', array('example.com'));
```

If you need other criteria than path, content type and hosts, use the
`invalidate` method.

### Any Header

You can also invalidate the cache based on any headers. If you use non-default
headers, make sure to configure your proxy accordingly to have them taken into
account. (See [varnish ban](varnish.md#ban).)

Cache client implementations should fill up the headers to at least have the
default headers always present to simplify the cache configuration rules.

To invalidate on a custom header X-My-Header, you would do:

```php
$cacheInvalidator->invalidate(array('X-My-Header' => 'my-value'));
```

Tags
----

Make sure to [configure your proxy for tagging](varnish.md#tagging) first.

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
$cacheInvalidator->invalidateTags(array('group-a', 'tag-four'));
```

This will ban all requests having either the tag `group-a` /or/ `tag-four`. In
the above example, this will invalidate "/two", "/three" and "/four". Only "/one"
will stay in the cache.

### Changing The Tags Header

Tagging uses a custom HTTP header to identify tags. You can change the default
header `X-Cache-Tags` by calling `setTagsHeader()`. Make sure to reflect this
change in your [caching proxy configuration](varnish.md#tagging).

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

### Logging errors

You can log any exceptions in the following way. First construct a logger that
implements `\Psr\Log\LoggerInterface`. For instance, when using
[Monolog](https://github.com/Seldaek/monolog):

```php
use Monolog\Logger;

$logger = new Logger(...);
$logger->pushHandler(...);
```

Then add the logger as a subscriber to the cache invalidator:

```php
use FOS\HttpCache\EventListener\LogSubscriber;

$subscriber = new LogSubscriber($logger);
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