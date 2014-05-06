# User Context Caching

The rendering of a view in your application may be different
if the user is authenticated, by his rights or by other information.

* [Overview](#overview)
* [HashGenerator](#hashgenerator)
* [Implementation](#implementation)
* [Varnish setup](#varnishsetup)

## Overview

This functionality can be implemented with the following workflow :

1. A client send a request to `/foo.php` url
2. The HttpProxy receive the request, extract the session id (a unique identifier for each user), and send an HEAD request on a specific url (like `/head.php`)
    * If not cached, the backend response contains a X-FOSHttpCache-Hash header, where the value is different for each category of user : user context

      If you want to cache this call you must vary the response on the unique identifier (here we use the X-FOSHttpCache-SessionId header)
  * If cached, the response is already available in the HttpProxy
3. The real request is then made to the `/foo.php` url

  This request can be cached with a Vary on the X-FOSHttpCache-Hash header. So all the clients which have the same hash will have the same response and only the first request will make a call to the backend, all others can be returned by the HttpProxy.

## HashGenerator

FOSHttpCache provide tool to generate an hash depending on the user context.

Your application must make sure to provide a [ContextProviderInterface](../src/UserContext/ContextProviderInterface.php)
for each context that influences the rendered output. The providers update the [UserContext](../src/UserContext/UserContext.php)
with parameters having an impact on the view.

Then, you need to register this provider in the [HashGenerator](../src/UserContext/HashGenerator.php)
with the `registerProvider` method.

Once all provider are registered you can call the `generateHash` method to get the Hash for the current user context.

## Implementation

FOSHttpCache does not implement transformation of the Response part. However, if you use
 Symfony2, an implementation is available in the [FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle).

Here is a simple implementation example :

AuthenticationService : 

```php
<?php

class AuthenticationService implements ContextProviderInterface
{
    ...

    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    public function updateUserContext(UserContext $context)
    {
        $context->addParameter('authenticated', $this->isAuthenticated());
    }
}

```

The head.php file, called by the first request. We say that this hash is valid for one hour, a good practice is to set the same ttl as your session one.

```php
<?php
// /head.php file
$authenticationService = new AuthenticationService();

$hashGenerator = new HashGenerator();
$hashGenerator->registerProvider($authenticationService);

if ('HEAD' == strtoupper($_SERVER['REQUEST_METHOD'])) {
    header(sprintf('X-FOSHttpCache-Hash: %s', $hashGenerator->generateHash()));
    header('Vary: X-FOSHttpCache-SessionId');
    header('Cache-Control: max-age=3600');
    exit;
}

header('HTTP/1.1 404');
```

The homepage which has a different view if the user is logged in or not.

```php
<?php
// /index.php file
header('Cache-Control: max-age=3600');
header('Vary: X-FOSHttpCache-Hash');

$authenticationService = new AuthenticationService();

if ($authenticationService->isAuthenticated()) {
    echo "You are authenticated";
} else {
    echo "You are anonymous";
}

```

## Varnish setup

### Varnish 3

Here is a vcl example on how to implement this functionality in varnish 3.x :

```
sub vcl_recv {
    // When a head request is made, a restart is perform, here we set the request parameters to the original one
    if (req.restarts > 0 && req.http.X-FOSHttpCache-OriginalMethod) {
        set req.request = req.http.X-FOSHttpCache-OriginalMethod;
        set req.url     = req.http.X-FOSHttpCache-OriginalUrl;

        unset req.http.X-FOSHttpCache-OriginalUrl;
        unset req.http.X-FOSHttpCache-OriginalMethod;
    }

    // Create the first request with HEAD method to get Hash
    if (req.restarts == 0 && req.http.cookie && (req.request == "GET" || req.request == "HEAD")) {
        set req.http.X-FOSHttpCache-TempCookie     = req.http.cookie;
        set req.http.X-FOSHttpCache-OriginalUrl    = req.url;
        set req.http.X-FOSHttpCache-OriginalMethod = req.request;
        set req.http.X-FOSHttpCache-SessionId      = req.http.cookie;

        set req.url     = "/user_context_head.php";
        set req.request = "HEAD";

        unset req.http.cookie;
    }
}

sub vcl_miss {
    // When creating backend request, varnish force GET method (bug ?)
    set bereq.request = req.request;

    // Varnish never cache if cookie are presents, so we remove then to use the cache,
    // and add them to the backend request when a miss occurred
    if (bereq.http.X-FOSHttpCache-TempCookie) {
        set bereq.http.cookie = bereq.http.X-FOSHttpCache-TempCookie;
    }
}

sub vcl_deliver {
    // When we receive the head response (in cache or not), we set the header for the real request and call it
    if (req.request == "HEAD" && resp.http.x-foshttpcache-hash) {
        set req.http.x-foshttpcache-hash = resp.http.x-foshttpcache-hash;

        return (restart);
    }
}
```

#### Extracting req.http.X-FOSHttpCache-SessionId

In this example we set that our unique identifier is the plain value of the cookie.

However, with google analytics, for example, this will failed, as the cookies will
change for each new page so the HEAD request will never be cached.

Here is a example on how to extract the session id with a basic php configuration :

```
set req.http.X-FOSHttpCache-SessionId = ";" + req.http.cookie;
set req.http.X-FOSHttpCache-SessionId = regsuball(req.http.X-FOSHttpCache-SessionId, "; +", ";");
set req.http.X-FOSHttpCache-SessionId = regsuball(req.http.X-FOSHttpCache-SessionId, ";(PHPSESSID)=", "; \1=");
set req.http.X-FOSHttpCache-SessionId = regsuball(req.http.X-FOSHttpCache-SessionId, ";[^ ][^;]*", "");
set req.http.X-FOSHttpCache-SessionId = regsuball(req.http.X-FOSHttpCache-SessionId, "^[; ]+|[; ]+$", "");
```

If the authentication of your user is not based on the PHPSESSID Cookie,
you will have to implement your own method to extract the
X-FOSHttpCache-SessionId header.
