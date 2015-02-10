.. _testing your application:

Testing Your Application
========================

This chapter describes how to test your application against you reverse proxy
instance.

The FOSHttpCache library provides base test case classes to help you write
functional tests. This may be helpful to test the way your application sets
caching headers and invalidates cached content.

By having your test classes extend one of the test case classes, you get:

* independent tests: all previously cached content is removed in the tests
  ``setUp`` method. The way this is done depends on which reverse proxy you use;
* an instance of this library’s client that is configured to talk to your
  reverse proxy server. See reverse proxy specific sections for details;
* convenience methods for executing HTTP requests to your application:
  ``$this->getHttpClient()`` and ``$this->getResponse()``;
* custom assertions ``assertHit`` and ``assertMiss`` for validating a cache hit/miss.

The recommended way to configure the test case is by setting constants
in your `phpunit.xml`. Alternatively, you can override the getter methods.

Setting Constants
~~~~~~~~~~~~~~~~~

Compare this library’s configuration to see how the constants are set:

.. literalinclude:: ../phpunit.xml.dist
    :prepend:
        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit ...>
    :language: xml
    :start-after: <php>

Overriding Getters
~~~~~~~~~~~~~~~~~~

You can override getters in your test class in the following way::

    use FOS\HttpCache\Test\VarnishTestCase;

    class YourFunctionalTest extends VarnishTestCase
    {
        protected function getVarnishPort()
        {
            return 8000;
        }
    }

VarnishTestCase
---------------

Configuration
'''''''''''''

By default, the ``VarnishTestCase`` starts and stops a Varnish server for you.
All you have to do is to set your Varnish configuration (VCL) file.

======================= ========================= ================================================== ============================================
Constant                Getter                    Default                                            Description
======================= ========================= ================================================== ============================================
``VARNISH_FILE``        ``getConfigFile()``                                                          your Varnish configuration (VCL) file
``VARNISH_BINARY``      ``getBinary()``           ``varnishd``                                       your Varnish binary
``VARNISH_PORT``        ``getCachingProxyPort()`` ``6181``                                           port Varnish listens on
``VARNISH_MGMT_PORT``   ``getVarnishMgmtPort()``  ``6182``                                           Varnish management port
``VARNISH_CACHE_DIR``   ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-varnish`` directory to use for cache
``VARNISH_VERSION``     ``getVarnishVersion()``   ``3``                                              installed varnish application version
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                            hostname your application can be reached at
======================= ========================= ================================================== ============================================

Enable Assertions
'''''''''''''''''

For the `assertHit` and `assertMiss` assertions to work, you need to add a
:ref:`custom X-Cache header <varnish_debugging>` to responses served
by your Varnish.

NginxTestCase
-------------

Configuration
'''''''''''''

By default, the ``NginxTestCase`` starts and stops NGINX server for you and deletes
all cached contents. You have to set your NGINX configuration file.

These are all the configurations you can change

======================= ========================= ================================================ ==========================================
Constant                Getter                    Default                                          Description
======================= ========================= ================================================ ==========================================
``NGINX_FILE``          ``getConfigFile()``                                                        your NGINX configuration file
``NGINX_BINARY``        ``getBinary()``           ``nginx``                                        your NGINX binary
``NGINX_PORT``          ``getCachingProxyPort()`` ``8088``                                         port NGINX listens on
``NGINX_CACHE_PATH``    ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-nginx`` directory to use for cache
                                                                                                   Must match `proxy_cache_path` directive in
                                                                                                   your configuration file.
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                          hostname your application can be reached at
======================= ========================= ================================================ ==========================================

Enable Assertions
'''''''''''''''''

For the `assertHit` and `assertMiss` assertions to work, you need to add a
:ref:`custom X-Cache header <nginx_debugging>` to responses served
by your Nginx.

Usage
-----

This example shows how you can test whether the caching headers your
application sets influence Varnish as you expect them to::

    use FOS\HttpCache\Test\VarnishTestCase;

    class YourFunctionalTest extends VarnishTestCase
    {
        public function testCachingHeaders()
        {
            // Varnish is restarted, so you don’t have to worry about previously
            // cached content. Before continuing, the VarnishTestCase waits for
            // Varnish to become available.

            // Retrieve an URL from your application
            $response = $this->getResponse('/your/resource');

            // Assert the response was a cache miss (came from the backend
            // application)
            $this->assertMiss($response);

            // Assume the URL /your/resource sets caching headers. If we retrieve
            // it again, we should have a cache hit (response delivered by Varnish):
            $response = $this->getResponse('/your/resource');
            $this->assertHit($response);
        }
    }

This example shows how you can test whether your application purges content
correctly::

    public function testCachePurge()
    {
        // Again, Varnish is restarted, so your test is independent from
        // other tests

        $url = '/blog/articles/1';

        // First request must be a cache miss
        $this->assertMiss($this->getResponse($url));

        // Next requests must be a hit
        $this->assertHit($this->getResponse($url));

        // Purge
        $this->varnish->purge('/blog/articles/1');

        // First request after must again be a miss
        $this->assertMiss($this->getResponse($url));
    }

Tests for Nginx look the same but extend the NginxTestCase.
For more ideas, see this library’s functional tests in the
:source:`tests/Functional/` directory.
