Varnish Configuration
=====================

This chapter describes how to configure Varnish to work with the library.

* [Introduction](#introduction)
* [Basic Varnish Configuration](#basic-varnish-configuration)
* [Purge](#purge)
* [Refresh](#refresh)
* [Ban](#ban)
* [Tagging](#tagging)
* [User Context](#user-context)
* [Debugging](#debugging)

Introduction
------------

Below you will find detailed Varnish configuration recommendations for the
features provided by this library. The examples are tested with Varnish
version 3.0. For a quick overview, you can also look at [the configuration
that is used for the library’s functional tests]
(../tests/Tests/Functional/Fixtures/varnish/).

Basic Varnish Configuration
---------------------------

To invalidate cached objects in Varnish, begin by adding an
[ACL](https://www.varnish-cache.org/docs/3.0/tutorial/vcl.html#example-3-acls)
to your Varnish configuration. This ACL determines which IPs are allowed to
issue invalidation requests. Let’s call the ACL `invalidators`. The ACL below
will be used throughout the Varnish examples on this page.

```varnish
# /etc/varnish/your_varnish.vcl

acl invalidators {
  "localhost";
  # Add any other IP addresses that your application runs on and that you
  # want to allow invalidation requests from. For instance:
  # "192.168.1.0"/24;
}
```

See also this library’s [basic configuration](../tests/Functional/Fixtures/varnish/fos.vcl).

Warning: Make sure that all web servers running your application that may
trigger invalidation are whitelisted here. Otherwise, lost cache invalidation
requests will lead to lots of confusion.

Purge
-----

To configure Varnish for [handling PURGE requests](https://www.varnish-cache.org/docs/3.0/tutorial/purging.html):

```varnish
# /etc/varnish/your_varnish.vcl

sub vcl_recv {
    if (req.request == "PURGE") {
        if (!client.ip ~ invalidators) {
          error 405 "PURGE not allowed";
        }
        return (lookup);
    }
}

sub vcl_hit {
    if (req.request == "PURGE") {
        purge;
        error 200 "Purged";
    }
}

sub vcl_miss {
    if (req.request == "PURGE") {
        purge;
        error 404 "Not in cache";
    }
}
```

See also this library’s [purge.vcl](../tests/Functional/Fixtures/varnish/purge.vcl).

Refresh
-------

If you want to invalidate cached objects by [forcing a refresh](https://www.varnish-cache.org/trac/wiki/VCLExampleEnableForceRefresh),
add the following to your Varnish configuration:

```varnish
sub vcl_recv {
    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ invalidators) {
        set req.hash_always_miss = true;
    }
}
```

See also this library’s [refresh.vcl](../tests/Functional/Fixtures/varnish/refresh.vcl).

Ban
---

To configure Varnish for [handling BAN requests](https://www.varnish-software.com/static/book/Cache_invalidation.html#banning):

```varnish
# /etc/varnish/your_varnish.vcl

sub vcl_recv {
    if (req.request == "BAN") {
        if (!client.ip ~ invalidators) {
            error 405 "Not allowed.";
        }

        ban("obj.http.x-host ~ " + req.http.x-ban-host
            + " && obj.http.x-url ~ " + req.http.x-ban-url
            + " && obj.http.x-content-type ~ " + req.http.x-ban-content-type
        );

        error 200 "Banned";
    }
}

sub vcl_fetch {
    # Set BAN lurker friendly tags on object
    set beresp.http.x-url = req.url;
    set beresp.http.x-host = req.http.host;
}

sub vcl_deliver {
    # Remove tags when delivering to client
    if (!resp.http.X-Cache-Debug) {
        unset resp.http.x-url;
        unset resp.http.x-host;
    }
}
```

See also this library’s [ban.vcl](../tests/Functional/Fixtures/varnish/ban.vcl).

Tagging
-------

Add the following to your Varnish configuration to enable [cache tagging](cache-invalidator.md#tags).
The custom `X-Cache-Tags` header should match the tagging header
[configured in the cache invalidator](cache-invalidator.md#custom-tags-header).

```varnish
sub vcl_recv {
    if (req.request == "BAN") {
        if (!client.ip ~ invalidators) {
            error 405 "Not allowed.";
        }

        if (req.http.x-cache-tags) {
            # Banning tags
            ban("obj.http.host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
                + " && obj.http.x-cache-tags ~ " + req.http.x-cache-tags
            );
        } else {
            # Not banning tags
            ban("obj.http.host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
            );
        }

        error 200 "Banned";
    }
}
```

See also this library’s [ban.vcl](../tests/Functional/Fixtures/varnish/ban.vcl).

User Context
------------

To configure your Varnish to support [user context hashing](user-context.md):

```varnish
sub vcl_recv {
    # Handle the original request: send a request with a
    # specific accept header to retrieve the user hash
    if (req.restarts == 0 && (req.http.cookie || req.http.authorization) && (req.request == "GET" || req.request == "HEAD")) {
        set req.http.x-original-url    = req.url;
        set req.http.x-original-accept = req.http.accept;

        set req.url         = "/user_context_hash.php";
        set req.http.accept = "application/vnd.fos.user-context-hash";

        #
        # By default, Varnish does not look for cache when a Cookie or
        # Authorization header is present.
        # See: https://www.varnish-cache.org/trac/browser/bin/varnishd/default.vcl?rev=3.0#L63
        #
        # You can force this lookup by uncommenting the line below.
        #
        #return (lookup);
    } elsif (req.restarts > 0 && req.http.accept ~ "application/vnd.fos.user-context-hash") {
        # After the hash request, reset the request to the original one, which
        # will be restarted in vcl_deliver.

        set req.http.accept = req.http.x-original-accept;
        set req.url         = req.http.x-original-url;

        unset req.http.req.http.x-original-method;
        unset req.http.req.http.x-original-url;

        # We do the original request with the user hash as provided by  the backend.
        # We want to look for cache even when the Cookie or Authorization header are present.
        # It is the responsibility of the backend to Vary on the user hash to separate cached data.
        return (lookup);
    }
}

sub vcl_deliver {
    # After receiving the hash response, copy the hash header
    # to the original request and restart that.
    if (resp.http.content-type ~ "application/vnd.fos.user-context-hash") {
        set req.http.x-user-context-hash = resp.http.x-user-context-hash;

        return (restart);
    }
}
```

See also this library’s [user_context.vcl](../tests/Functional/Fixtures/varnish/user_context.vcl).

## Extract a correct user identifier

When caching the hash request, we consider that a Cookie or Authorization header
contain the user identifier (like PHPSESSID cookie).

However, in some situations, for instance when using Google Analytics, cookie
values are different for each request. Because of this, the hash request will
not be cached. To make that request cacheable, we must extract a stable session
id and store that in the `X-User-Id` header.

We can do this as [explained in the varnish documentation](https://www.varnish-cache.org/trac/wiki/VCLExampleRemovingSomeCookies#RemovingallBUTsomecookies):

```varnish
set req.http.X-User-Id = ";" + req.http.cookie;
set req.http.X-User-Id = regsuball(req.http.X-User-Id, "; +", ";");
set req.http.X-User-Id = regsuball(req.http.X-User-Id, ";(PHPSESSID)=", "; \1=");
set req.http.X-User-Id = regsuball(req.http.X-User-Id, ";[^ ][^;]*", "");
set req.http.X-User-Id = regsuball(req.http.X-User-Id, "^[; ]+|[; ]+$", "");
```

If your application’s user authentication is based on cookie other than
PHPSESSID, change `PHPSESSID` to your cookie name.

We also need to change the Vary header in the hash response.

```php
header('Vary: X-User-Id');
```

Debugging
---------

Configure your Varnish to set a debug header that shows whether a cache hit or miss occurred:

```varnish
sub vcl_deliver {
    # Add extra headers if debugging is enabled
    if (resp.http.x-cache-debug) {
        if (obj.hits > 0) {
            set resp.http.X-Cache = "HIT";
        } else {
            set resp.http.X-Cache = "MISS";
        }
    }
}
```

See also this library’s [debug.vcl](../tests/Functional/Fixtures/varnish/debug.vcl).
