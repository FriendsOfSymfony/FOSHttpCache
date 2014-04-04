Varnish Client
==============

The Varnish client supports all [cache invalidation methods](proxy-clients.md).

* [Setup](#setup)
* [Usage](#usage)

Setup
-----

At minimum, supply an array containing IPs or hostnames of the Varnish servers
that you want to send invalidation requests to. Make sure to include the port
Varnish runs on if it is not port 80.

```php
use FOS\HttpCache\ProxyClient\Varnish;

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

The Varnish client supports all cache invalidation methods:

1. [purge](proxy-clients.md#purge); make sure to first [configure Varnish for purge](varnish-configuration.md#purge)
2. [refresh](proxy-clients.md#refresh); make sure to first [configure Varnish for refresh](varnish-configuration.md#refresh)
3. [ban](proxy-clients.md#ban); make sure to first [configure Varnish for ban](varnish-configuration.md#ban).

Further reading:
* See the [Caching Proxy Clients](proxy-clients.md) chapter for more information
  on how to use the invalidation methods.
* See the [Varnish Configuration](varnish-configuration.md) chapter for more on
  preparing your Varnish server for handling invalidation requests.
