Varnish Client
==============

The Varnish client supports all [cache invalidation methods](proxy-clients.md).

* [Setup](#setup)
* [Usage](#usage)
  * [Purge](#purge)
  * [Refresh](#refresh)
  * [Ban](#ban)

Setup
-----

At minimum, supply an array containing IPs or hostnames of the Varnish servers
that you want to send invalidation requests to. Make sure to include the port
Varnish runs on if it is not port 80.

```php
use FOS\HttpCache\Invalidation\Varnish;

$servers = array('10.0.0.1', '10.0.0.2:6081'); // Port 80 assumed for 10.0.0.1
$varnish = new Varnish($servers);
```

This is sufficient for invalidating absolute URLs. If you also wish to
invalidate relative paths, supply the hostname (or base URL) where your website
is available as the second parameter:

```php
$varnish = new Varnish($servers, 'my-cool-app.com');
```

Usage
-----

The Varnish clients supports all cache invalidation methods:
* [purge](proxy-clients.md#purge)
* [refresh](proxy-clients.md#refresh)
* [ban](proxy-clients.md#ban).

Make sure to [configure your Varnish servers](varnish-configuration.md) for
each method that you wish to use.

Purge
-----

Make sure to first [configure Varnish for purge](varnish-configuration.md#purge).

```php
$proxy
    ->purge('/my/path')
    ->purge('http://myapp.dev/absolute/url')
    ->flush()
;
```

Read more about [purging](proxy-clients.md#purgeinterface).

Refresh
-------

Make sure to first [configure Varnish for refresh](varnish-configuration.md#refresh).

```php
$varnish
    ->refresh('/my/path')
    ->refresh('http://myapp.dev/absolute/url')
    ->flush()
;
```

Read more about [refreshing](proxy-clients.md#refreshinterface).

Ban
---

Make sure to first [configure Varnish for ban](varnish-configuration.md#ban).

```php
$varnish->banPath('.*png$')
    ->banPath('/articles/.*', 'text/html')
    ->banPath('.*png$', null, 'example.com')
    ->flush()
;
```

To ban an array of header regular expressions:

```php
$varnish
    ->ban(
        array(
            Varnish::HTTP_HEADER_URL => '.*\.png$',
            Varnish::HTTP_HEADER_HOST => '.*example\.com',
            Varnish::HTTP_HEADER_CACHE => 'my-tag',
        )
    )
    ->flush()
;
```

Read more about [banning](proxy-clients.md#baninterface).