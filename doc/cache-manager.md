The Cache Manager
=================

Use the CacheManager to explicitly invalidate or refresh paths, URLs or
headers.

* [Invalidating paths and URLs](#invalidating-paths-and-urls)
* [Refreshing paths and URLs](#refreshing-paths-and-urls)
* [Invalidating with a Regular Expression](#invalidating-with-a-regular-expression)
* [Tags](#tags)
* [Flushing](#flushing)

Invalidating paths and URLs
---------------------------

Make sure to configure your proxy for purging first.
(See [varnish](varnish.md#purge).)

Invalidate a path:

```php
$cacheManager->invalidatePath('/users');
```

Invalidate an URL:
```php
$cacheManager->invalidatePath('http://www.example.com/users');
```

Refreshing paths and URLs
-------------------------

Make sure to configure your proxy for refreshing first.
(See [varnish](varnish.md#refresh).)

Refresh a path:

```php
$cacheManager->refreshPath('/users');
```

Refresh an URL:

```php
$cacheManager->refreshPath('http://www.example.com/users');
```

Invalidating with a Regular Expression
--------------------------------------

Make sure to configure your proxy for regular expressions first.
(See [varnish ban](varnish.md#ban).)

You can invalidate all URLs matching a regular expression by using the
`invalidateRegex` method:

For instance, to invalidate all .png files for all host names handled by this
caching proxy:

```php
$cacheManager->invalidateRegex('.*png$');
```

If you need other criteria than the path, directly access the cache client.
See for example [Varnish](varnish.md#ban).

Fluent interface
----------------

The cache manager offers a fluent interface:

```php
$cacheManager
    ->invalidatePath('/bad/guys')
    ->invalidatePath('/good/guys')
    ->refreshPath('/')
;
```

Tags
----

Make sure to [configure your proxy for tagging](varnish.md#tagging) first.
The examples in this section assume you left the `tagsHeader` unchanged. You
can call `CacheManager::setTagsHeader` to change the HTTP header used to
identify tags.

You will have to make sure your web application adds the correct tags on all
responses. The [HttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle)
provides means to help you with this, but without Symfony there is no generic
way to do this.

Assume you sent 3 responses:

* /one had the header `X-Cache-Tags: tag-one`
* /two had the header `X-Cache-Tags: tag-two, group-a`
* /three had the header `X-Cache-Tags: tag-three, group-a`
* /four had the header `X-Cache-Tags: tag-four, group-b`

You can now invalidate some URLs using tags:

```php
$cacheManager->invalidateTags(array('group-a', 'tag-four'));
```

This will ban all requests having either the tag group-a OR tag-four. In the
above example, this will invalidate "/two", "/three" and "/four". Only "/one"
will stay in the cache.

Flushing
--------

The CacheManager internally queues the invalidation requests and only sends
them out to your HTTP proxy when you call `flush()`:

```php
$cacheManager
    ->invalidateRoute(...)
    ->invalidatePath(...)
    ->flush()
;
```

Note: When using the Symfony Bundle, the cache manager is automatically
flushed. When using the Bundle, you only need to manually call flush when not
in a request context. (E.g. from a command.)

To keep the performance impact of sending invalidation requests to a minimum,
make sure to only flush /after/ the response has been sent to the client’s
browser.

The Varnish client also sends all invalidation requests in parallel to further
reduce the time used by invalidation.
