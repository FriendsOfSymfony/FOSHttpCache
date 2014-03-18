Nginx Client
============

The Nginx client supports [purge and refresh](proxy-clients.md).

* [Setup](#setup)
* [Usage](#usage)

Setup
-----

At minimum, supply an array containing IPs or hostnames of the Nginx servers
that you want to send invalidation requests to. Make sure to include the port
Nginx runs on if it is not port 80.

```php
use FOS\HttpCache\Invalidation\Nginx;

$servers = array('10.0.0.1', '10.0.0.2:6183'); // Port 80 assumed for 10.0.0.1
$nginx = new Nginx($servers);
```

This is sufficient for invalidating absolute URLs. If you also wish to
invalidate relative paths, supply the hostname (or base URL) where your website
is available as the second parameter:

```php
$nginx = new Nginx($servers, 'my-cool-app.com');
```

If you have configured Nginx to support purge requests at a separate location,
supply that location to the class as the third parameter:

```php
$nginx = new Nginx($servers, 'my-cool-app.com', 'purge');
```

Usage
-----

The Nginx client supports purge and refresh:

1. [purge](proxy-clients.md#purge); make sure to first [configure Nginx for purge](nginx-configuration.md#purge)
2. [refresh](proxy-clients.md#refresh); make sure to first [configure Nginx for refresh](nginx-configuration.md#refresh).

Further reading:
* See the [Caching Proxy Clients](proxy-clients.md) chapter for more information
  on how to use the invalidation methods.
* See the [Nginx Configuration](nginx-configuration.md) chapter for more on
  preparing your Nginx server for handling invalidation requests.
