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
a general reverse proxy like NGINX or Varnish.

Invalidation works by setting the specific LiteSpeed headers on the **response**. This means
contrary to other proxies in this library, we do not send any ``PURGE`` requests to
the proxy but instead we have to send a request to an endpoint where the response provides
the correct LiteSpeed-specific headers which then trigger purging actions.
You can read more on these headers in the `LiteSpeed response headers documentation`_.

To do so, we generate a simple PHP file with a random file name containing the appropriate ``header()`` calls.
After generation, we request this file and delete it again right away.

For this reason you have to configure two parameters:

* The location on your server where the file should be generated to
  (must be publicly accessible and writable by the server).
* The base URL on which the generated file can be requested.

Configuring LiteSpeed WebServer itself
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Enabling LiteSpeed to support public caching on your server is pretty much straight forward.
Add this to your ``.htaccess``::

    <IfModule LiteSpeed>
        CacheEnable public /
    </IfModule>

.. note::

    This setup works in a single node webserver environment only. If you are targeting a multi
    node setup you might want to consider switching to :ref:`Varnish <varnish configuration>` which has excellent
    support for this setup already built-in in this library.

You can find more information on how to `configure LiteSpeed`_ in their docs.

Configuring the library
~~~~~~~~~~~~~~~~~~~~~~~

To illustrate configuration it's easiest if we do so with an example. For this we assume you have the following setup:

* Your domain is called ``www.example.com``
* Your domain points to ``/var/www/public``


Your proxy client instance has to look like so::

    use FOS\HttpCache\ProxyClient\HttpDispatcher;
    use FOS\HttpCache\ProxyClient\LiteSpeed;

    $servers = ['https://www.example.com'];
    $baseUri = 'https://www.example.com';
    $httpDispatcher = new HttpDispatcher($servers, $baseUri);

    $options = [
        'target_dir' => '/var/www/public',
    ];

    $litespeed = new LiteSpeed($httpDispatcher, $options);

.. _configure LiteSpeed: https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:no-plugin-setup-guidline
.. _LiteSpeed response headers documentation:  https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:developer_guide:response_headers
