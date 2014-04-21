Caching Proxy Clients
=====================

You can use the caching proxy clients either wrapped by the cache invalidator
(recommended), or directly for low-level access to invalidation functionality.

* [Setup](#setup)
* [ProxyClientInterface](#proxyclientinterface)
* [Purge](#purge)
* [Refresh](#refresh)
* [Ban](#ban)

Setup
-----

You should set up at least one caching proxy client:

* [Varnish client](varnish-client.md)
* Nginx client

Then continue here to find out how to use the proxy clients.

ProxyClientInterface
-------------------

Each client is an implementation of [ProxyClientInterface](../src/ProxyClient/ProxyClientInterface.php).
All other interfaces, `PurgeInterface`, `RefreshInterface` and `BanInterface`
extend this `ProxyClientInterface`. So each client implements at least one of
the three [invalidation methods](invalidation-introduction.md#invalidation-methods),
depending on the caching proxy’s abilities.

The `ProxyClientInterface` has one method: `flush()`. After collecting
invalidation requests, `flush()` needs to be called to actually send the
requests to the caching proxy. This is on purpose: this way, we can send
all requests together, reducing the performance impact of sending invalidation
requests.

Purge
-----

If the caching proxy understands [purge requests](invalidation-introduction.md#purge),
its client should implement `PurgeInterface`. Use the `purge($url)` method to
purge one specific URL. The URL can be either an absolute URL or a relative
path:

```php
$client
    ->purge('http://my-app.com/some/path')
    ->purge('/other/path')
    ->flush()
;
```

Refresh
-------

If the caching proxy understands [refresh requests](invalidation-introduction.md#refresh),
its client should implement `RefreshInterface`. Use the
`refresh($url, array $headers = array())` method to refresh one specific
URL. The URL can be either an absolute URL or a relative path:

```php
$client
    ->refresh('http://my-app.com/some/path')
    ->refresh('other/path')
    ->flush()
;
```

You can also specify HTTP headers. For instance, to only refresh the JSON
representation of an URL:

```php
$client
    ->refresh('/some/path', array('Accept' => 'application/json')
    ->flush()
;
```

Ban
---

If the caching proxy understands [ban requests](invalidation-introduction.md#ban),
its client should implement `BanInterface`.

You can invalidate all URLs matching a regular expression by using the
`banPath($path, $contentType, $hosts)` method. It accepts a regular expression
for the path to invalidate and an optional content type regular expression and
list of application hostnames.

For instance, to ban all .png files on all application hosts:

```php
$client->banPath('.*png$');
```

To ban all HTML URLs that begin with `/articles/`:

```php
$client->banPath('/articles/.*', 'text/html');
```

By default, URLs will be banned on all application hosts. You can limit this by
specifying a host header:

```php
$client->banPath('*.png$', null, '^www.example.com$');
```

If you want to go beyond banning combinations of path, content type and hostname,
use the `ban(array $headers)` method. This method allows you to specify any
combination of headers that should be banned. For instance, when using the
[Varnish client](varnish-client.md):

```php
$varnish->ban(array(
    Varnish::HTTP_HEADER_URL   => '.*\.png$',
    Varnish::HTTP_HEADER_HOST  => '.*example\.com',
    Varnish::HTTP_HEADER_CACHE => 'my-tag',
));
```

Make sure to add any headers that you want to ban on to your
[caching proxy configuration](proxy-configuration.md).
