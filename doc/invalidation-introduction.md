An Introduction to Cache Invalidation
=====================================

This general introduction explains cache invalidation concepts. If you are
already familiar with cache invalidation, you may wish to skip this chapter
and continue to the [Cache Invalidator](cache-invalidator.md).

What is Cache Invalidation?
---------------------------

Cache invalidation, also known as cache purging, ...

Cache Invalidation Methods
--------------------------

There are three methods to invalidate content.

### Purge

**Purge** invalidates a specific URL in all its variants (as specified by the
VARY header) and with all query strings. In the case of Varnish, this will
remove the entries from the cache immediately.

### Refresh

**Refresh** is similar to purge: invalidates a specific URL with all query strings,
but not its variants. Do an immediate request to the backend to have the page
in the cache again.

### Ban

**Ban** is a way more powerful operation. Remove all requests matching
specified regular expressions on any desired headers. This can invalidate a
subset of URLs but also custom headers, as used with the
CacheInvalidator::invalidateTags method. In the case of Varnish, this will only
record the fact that cache entries are to be ignored, for performance
reasons.

Now, start invalidating content with:
* the [Cache Invalidator](cache-invalidator.md)
* or the low-level [Caching Proxy Clients](caching-proxy.md).