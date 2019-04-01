.. _litespeed configuration:

LiteSpeed Configuration
-----------------------

Below you will find detailed LiteSpeed configuration recommendations for the
features provided by this library.

Preamble
~~~~~~~~

First of all, let's get one thing straight here: You'll find a lot of documentation
and noise around LiteSpeed cache on the Internet, mostly involving plugins, specifically the
Wordpress one. You **don't** need any plugin to benefit from LiteSpeed cache!
As long as you follow the HTTP specification regarding the caching headers, you can use it as
a general reverse proxy just like NGINX or Varnish.

LiteSpeed comes in two different variants:

* OpenLiteSpeed (OLS) - the open source product with less features
* LiteSpeed Web Server (LSWS) - the enterprise version with more features and professional support

The caching module implementations are different and thus have to be configured differently but they support the
same set of features (except for OLS not supporting Edge Side Includes (ESI)).

So before you start configuring the server, make sure you know which version of LiteSpeed you are using.

.. note::

    Any LiteSpeed setup works in a single node web server environment only. If you are targeting a multi
    node setup you might want to consider switching to :ref:`Varnish <varnish configuration>` which has excellent
    support for this already built-in in this library.


Configuring OpenLiteSpeed
~~~~~~~~~~~~~~~~~~~~~~~~~

OLS does not support different caching settings depending on ``.htaccess`` settings and different paths.
If you need that, you have to go with LSWS instead.
Thus, OLS has to be configured as follows on server or vHost level::

    module cache {
      # This enables the public cache
      enableCache                      1

      # This disables the private cache
      enablePrivateCache               0

      # This enables the public cache
      checkPublicCache                 1

      # This disables the private cache
      checkPrivateCache                0

      # Also consider the query string in caches
      qsCache                          1

      # Disable checking for a cached entry if there's a cookie on the request
      reqCookieCache                   0

      # We ignore request Cache-Control headers
      ignoreReqCacheCtrl               1

      # Must be disabled, this tells LS to check the Cache-Control/Expire headers on the response
      ignoreRespCacheCtrl              0

      # We don't cache responses that set a cookie
      respCookieCache                  0

      # Configure the maximum stale age to a sensible value for your application
      # The maxStaleAge defines a grace period in which LS can use an out of date (stale) response while checking on a new version
      maxStaleAge                      10

      # Make sure we disable expireInSeconds because it would override our Cache-Control header
      expireInSeconds                  0

      # If you want to use cache invalidation by cache tags, configure the general PURGE endpoint here.
      # By default, this library is configured to use "/_fos_litespeed_purge_endpoint". If you'd like to
      # have a different one, configure it here accordingly and make sure you configure the same URI
      # in the library itself (see further down this page). Also make sure you don't overlook trailing slashes here!
      purgeuri                         /_fos_litespeed_purge_endpoint

      # Enable the module
      ls_enabled                       1
    }

That's all you need. Of course you might want to adjust certain values to your needs.
Also refer to the OLS docs if you need more details about the different configuration values.

Configuring LiteSpeed WebServer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

LSWS can also be configured on server level, however, as LSWS supports ``.htaccess`` you may want to configure it
there to give your application the flexibility of having different configurations for certain paths.

Configure your ``.htaccess`` as follows::

    <IfModule LiteSpeed>
        CacheEnable public /
        # TODO: The rest of the directives
    </IfModule>

Also refer to the LSWS docs if you need more details about the different configuration values.

Configuring FOSHttpCache to work with LiteSpeed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Because LiteSpeed does not support a multi node setup configuring the proxy client is pretty straight forward if
you serve your application only on one domain::

    use FOS\HttpCache\ProxyClient\HttpDispatcher;
    use FOS\HttpCache\ProxyClient\LiteSpeed;

    $servers = ['127.0.0.1'];
    $baseUri = 'example.com';
    $httpDispatcher = new HttpDispatcher($servers, $baseUri);

    $litespeed = new LiteSpeed($httpDispatcher);

If you need multiple domains, make your ``$baseUri`` an array like so::

    use FOS\HttpCache\ProxyClient\HttpDispatcher;
    use FOS\HttpCache\ProxyClient\LiteSpeed;

    $servers = ['127.0.0.1'];
    $baseUris = ['example.com', 'foobar.com'];
    $httpDispatcher = new HttpDispatcher($servers, $baseUris);

    $litespeed = new LiteSpeed($httpDispatcher);


If you configured your LiteSpeed instance to use a different ``purgeuri`` than ``/_fos_litespeed_purge_endpoint`` also
make sure to pass the configured URI like so::

    use FOS\HttpCache\ProxyClient\HttpDispatcher;
    use FOS\HttpCache\ProxyClient\LiteSpeed;

    $servers = ['127.0.0.1'];
    $baseUris = ['example.com', 'foobar.com'];
    $httpDispatcher = new HttpDispatcher($servers, $baseUris);

    $litespeed = new LiteSpeed($httpDispatcher, ['purge_endpoint' => '/your-uri');

Cache Tagging
~~~~~~~~~~~~~

If you want to use cache tagging please note that you cannot use the default settings of the ``ResponseTagger`` (which
by default uses  ``X-Cache-Tags``) but instead you have to configure it to ``X-LiteSpeed-Tag`` like so::

    use FOS\HttpCache\ResponseTagger;
    use FOS\HttpCache\TagHeaderFormatter;

    $formatter = new CommaSeparatedTagHeaderFormatter('X-LiteSpeed-Tag');
    $responseTagger = new ResponseTagger(['header_formatter' => $formatter]);


