Nginx
=======

This chapter describes how to configure Nginx to work with the library.

* [Introduction](#introduction)
* [Basic NGINX configuration](#basic-nginx-configuration)
* [Purge](#purge)
* [Refresh](#refresh)

Introduction
------------
[NGINX](https://nginx.org) is a good choice for a caching reverse proxy. 
This document is not meant to be an introduction to NGINX, so if you are not familiar with it, you might want to read some tutorial first.


Below, you will find detailed NGINX configuration recommendations for the features provided by this library. The examples are tested with NGINX version 1.4.5. For a quick overview, you can also look at [the configuration that is used for the library’s functional tests] (../tests/Tests/Functional/Fixtures/nginx/fos.conf).

Basic NGINX configuration
---------------------------

Purge
-----

NGINX doesn't provide cache purgin out of the box. But you easely add it by installing [ngx_cache_purge](http://labs.frickle.com/nginx_ngx_cache_purge/) module.

Here a [tutorial](http://mcnearney.net/blog/2010/2/28/compiling-nginx-cache-purging-support/) to compile NGINX with the ngx_cache_purge 

```NGINX
# /etc/nginx/your_site.conf

```

Refresh
-------

If you want to invalidate cached objects by forcing a refresh you have to use [proxy_cache_bypass]( http://wiki.nginx.org/HttpProxyModule#proxy_cache_bypass)

There are many ways to have a request bypass the cache. This library use a custom HTTP header named [x-fos-refresh]. 

```NGINX
# /etc/nginx/your_site.conf
```
