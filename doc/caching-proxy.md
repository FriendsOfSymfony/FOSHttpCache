HTTP proxy
==========

Use the HTTP proxy classes (currently this library provides only a client for
Varnish) for lower-level access to invalidation functionality offered by your
HTTP proxy.

As with the CacheInvalidator, you need to call `flush()` to actually send requests
to the backend.

The CacheInvalidator is configured with an instance of CacheProxyInterface which
also implements at least one of PurgeInterface, RefreshInterface, BanInterface.

* [Purge](#purge)
* [Ban](#ban)
* [Refresh](#refresh)

Purge
-----

Make sure to [configure your proxy for purging](varnish.md#purge) first.

```php
$proxy
    ->purge('/my/path')
    ->purge('http://myapp.dev/absolute/url')
;
```

Refresh
-------

Make sure to [configure your proxy for refreshing](varnish.md#refresh) first.

You can refresh a path or an absolute URL by calling the `refresh` method:

```php
$varnish
    ->refresh('/my/path')
    ->refresh('http://myapp.dev/absolute/url')
;
```

Ban
---

Make sure to [configure your proxy for banning](varnish.md#ban) first.

The basic `ban` method just allows to send any headers you need to limit what
content is being invalidated. There is a convenience method `banPath` that
accepts a regular expression for the URL to invalidate, and an optional content
type regular expression and a list of host names.

You can invalidate all URLs matching a regular expression by using the
`ban` method:

For instance, to ban all .png files on all hosts:

```php
$varnish->banPath('.*png$');
);
```

To ban all HTML URLs that begin with `/articles/`:

```php
$varnish->banPath('/articles/.*', 'text/html');
```

By default, URLs will be banned on all hosts. You can limit this by specifying
a host header:

```php
$varnish->banPath('*.png$', null, 'example.com');
```

If you want to go beyond that, you can use the `ban` method directly:

```php
$varnish->ban(array(
    Varnish::HTTP_HEADER_URL => '.*\.png$',
    Varnish::HTTP_HEADER_HOST => '.*example\.com',
    Varnish::HTTP_HEADER_CACHE => 'my-tag',
));

You can also add your own headers to the [varnish ban code](varnish.md#ban) if
you need more.
