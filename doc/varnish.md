Varnish
=======

This chapter describes how to configure Varnish to work with the library.

* [Introduction](#introduction)
* [Basic Varnish configuration](#basic-varnish-configuration)
* [Purge](#purge)
* [Refresh](#refresh)
* [Ban](#ban)
* [Tagging](#tagging)

Introduction
------------

The [Varnish reverse caching proxy](https://www.varnish-cache.org) is a good
choice for a caching proxy. This document is not meant to be an introduction to
Varnish, so if you are not familiar with it, you might want to read some
tutorial first.

Below, you will find detailed Varnish configuration recommendations for the
features provided by this library. The examples are tested with Varnish
version 3.0. For a quick overview, you can also look at [the configuration
that is used for the library’s functional tests]
(../tests/Tests/Functional/Fixtures/varnish/fos.vcl).

Basic Varnish configuration
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
      ban("obj.http.x-host ~ " + req.http.x-ban-host + " && obj.http.x-url ~ " + req.http.x-ban-url + " && obj.http.x-content-type ~ " + req.http.x-ban-content-type);
      error 200 "Banned";
  }
}

# Set BAN lurker friendly tags on object
sub vcl_fetch {
  set beresp.http.x-url = req.url;
  set beresp.http.x-host = req.http.host;
  set beresp.http.x-content-type = req.http.content-type;
}

# Remove tags when delivering to client
sub vcl_deliver {
  if (! resp.http.X-Cache-Debug) {
    unset resp.http.x-url;
    unset resp.http.x-host;
    unset resp.http.x-content-type;
  }
}
```

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

Tagging
-------

Add the following to your Varnish configuration to enable [cache tagging](cache-invalidator.md#tags).

```varnish
sub vcl_recv {
    # ...

    if (req.request == "BAN") {
        # ...
        if (req.http.x-cache-tags) {
            ban("obj.http.host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
                + " && obj.http.x-cache-tags ~ " + req.http.x-cache-tags
            );
        } else {
            ban("obj.http.host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
            );
        }

        error 200 "Banned";
    }
}
```
