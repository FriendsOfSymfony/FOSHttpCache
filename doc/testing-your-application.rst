.. _testing your application:

Testing Your Application
========================

This chapter describes how to test your application against your reverse proxy.
By running your tests against a live instance of your proxy server, you can
validate the caching headers that your application sets, and the invalidation
rules that it defines.

The FOSHttpCache library provides traits and base test classes to help you
write functional tests with PHPUnit 5.7 and 6.*. Using the traits, you can
extend your own (or your framework’s) base test classes. For convenience, you
can also extend the FOSHttpCache base test class suitable for your proxy
server, which includes a sensible set of traits.

.. versionadded:: 2.1

    The testing has been updated to support PHPUnit 6 in version 2.1. If you
    an older version of FOSHttpCache and want to use the features described in
    this chapter, you need to use PHPUnit 5 to run the tests.

By using the traits, you get:

* independent tests: all previously cached content is removed in the test’s
  ``setUp()`` method;
* an instance of this library’s proxy client that is configured to talk to your
  proxy server for invalidation requests;
* a convenience method for executing HTTP requests to your proxy server:
  ``$this->getResponse()``;
* custom assertions ``assertHit()`` and ``assertMiss()`` for validating a cache
  hit/miss.

Configuration
-------------

The recommended way to configure the test case is by setting constants
in your ``phpunit.xml``. Alternatively, you can override the getter methods.

Web Server
~~~~~~~~~~

You will need to run a web server to provide the PHP application you want to
test. The test cases only handle running the proxy server. It’s easiest to
use PHP’s built in web server. Include the WebServerListener in your
``phpunit.xml``:

.. literalinclude:: ../phpunit.xml.dist
    :prepend:
        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit ...>
            <listeners>
    :language: xml
    :start-after: <listeners>
    :end-before: </listeners>
    :append:
            </listeners>
        </phpunit>

Then set the ``webserver`` group on your test to start PHP’s web server before
it runs::

    class YourTest extends \PHPUnit_Framework_TestCase
    {
        /**
         * @group webserver
         */
        public function testYourApp()
        {
            // The web server will be started before this test code runs and
            // shut down again after it finishes.
        }
    }

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

Traits
------

Proxy Server Traits
~~~~~~~~~~~~~~~~~~~

FOSHttpCache provides three proxy server traits that:

* if necessary, start your proxy server before running the tests;
* clear any cached content between tests to guarantee test isolation;
* if necessary, stop the proxy server after the tests have finished;
* provide ``getProxyClient()``, which returns the right
  :doc:`proxy client <proxy-clients>` for your proxy server.

You only need to include one of these traits in your test classes. Which one
you need (``VarnishTest``, ``NginxTest`` or ``SymfonyTest``) depends on the
proxy server that you use.

VarnishTest Trait
"""""""""""""""""

.. include:: includes/symfony-process.rst

Then configure the following parameters. The web server hostname and path to
your VCL file are required.

Then set your Varnish configuration (VCL) file. Configuration is handled either
by overwriting the getter or by defining a PHP constant. You can set the
constants in your ``phpunit.xml`` or in the bootstrap file. Available
configuration parameters are:

======================= ========================= ================================================== ===========================================
Constant                Getter                    Default                                            Description
======================= ========================= ================================================== ===========================================
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                            hostname your application can be reached at
``VARNISH_FILE``        ``getConfigFile()``                                                          your Varnish configuration (VCL) file
``VARNISH_BINARY``      ``getBinary()``           ``varnishd``                                       your Varnish binary
``VARNISH_PORT``        ``getCachingProxyPort()`` ``6181``                                           port Varnish listens on
``VARNISH_MGMT_PORT``   ``getVarnishMgmtPort()``  ``6182``                                           Varnish management port
``VARNISH_CACHE_DIR``   ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-varnish`` directory to use for cache
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                            hostname your application can be reached at
======================= ========================= ================================================== ===========================================

The Varnish version is controlled by an environment variable (in case you want
to test both Varnish 3 and 4 on a continuous integration system). See the
``.travis.yml`` of the FOSHttpCache git repository for an example.

==================== ========================= ======= ===========================================
Environment Variable Getter                    Default Description
==================== ========================= ======= ===========================================
``VARNISH_VERSION``  ``getVarnishVersion()``   ``4``   version of Varnish application that is used
==================== ========================= ======= ===========================================

See ``tests/bootstrap.php`` for an example how this repository uses the version
information to set the right ``VARNISH_FILE`` constant.

Enable Assertions
'''''''''''''''''

For the `assertHit` and `assertMiss` assertions to work, you need to add a
:ref:`custom X-Cache header <varnish_debugging>` to responses served
by your Varnish.

NginxTest Trait
"""""""""""""""

.. include:: includes/symfony-process.rst

Then configure the following parameters. The web server hostname and path to
your NGINX configuration file are required.

======================= ========================= ================================================ ===========================================
Constant                Getter                    Default                                          Description
======================= ========================= ================================================ ===========================================
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                          hostname your application can be reached at
``NGINX_FILE``          ``getConfigFile()``                                                        your NGINX configuration file
``NGINX_BINARY``        ``getBinary()``           ``nginx``                                        your NGINX binary
``NGINX_PORT``          ``getCachingProxyPort()`` ``8088``                                         port NGINX listens on
``NGINX_CACHE_PATH``    ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-nginx`` directory to use for cache
                                                                                                   Must match `proxy_cache_path` directive in
                                                                                                   your configuration file.
======================= ========================= ================================================ ===========================================

SymfonyTest Trait
"""""""""""""""""

It is assumed that the web server you run for the application has the HttpCache
integrated.

======================= ========================= ================================================ ===========================================
Constant                Getter                    Default                                          Description
======================= ========================= ================================================ ===========================================
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                          Hostname your application can be reached at
``WEB_SERVER_PORT``     ``getCachingProxyPort()``                                                  The port on which the web server runs
``SYMFONY_CACHE_DIR``   ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-nginx`` directory to use for cache
                                                                                                   Must match the configuration of your
                                                                                                   HttpCache and must be writable by the user
                                                                                                   running PHPUnit.
======================= ========================= ================================================ ===========================================

HttpCaller Trait
~~~~~~~~~~~~~~~~

Provides your tests with a ``getResponse`` method, which retrieves a URI from
your application through a real HTTP call that goes through the HTTP proxy server::

    use FOS\HttpCache\Test\HttpCaller;
    use PHPUnit\Framework\TestCase;

    class YourTest extends TestCase
    {
        use HttpCaller;

        public function testCachingHeaders()
        {
            // Get some response from your application
            $response = $this->getResponse('/path');

            // Optionally with request headers and a custom  method
            $response = $this->getResponse('/path', ['Accept' => 'text/json'], 'PUT');
        }
    }

This trait requires the methods ``getHostName()`` and ``getCachingProxyPort()``
to exist. When using one of the proxy server traits, these will be provided by
the trait, otherwise you have to implement them.

CacheAssertions Trait
~~~~~~~~~~~~~~~~~~~~~

Provides cache hit/miss assertions to your tests. To enable the these
``assertHit`` and ``assertMiss`` assertions, you need to configure your proxy
server to set an ``X-Cache`` header with the cache status:

* :ref:`Varnish <varnish_debugging>`
* :ref:`NGINX <nginx_debugging>`
* :ref:`Symfony HttpCache <symfony-cache x-debugging>`

Then use the assertions as follows::

    use FOS\HttpCache\Test\CacheAssertions;
    use PHPUnit\Framework\TestCase;

    class YourTest extends TestCase
    {
        public function testCacheHitOrMiss()
        {
            // Assert the application response is a cache miss
            $this->assertMiss($response);

            // Or assert it is a hit
            $this->assertHit($response);
        }
    }

Base Classes for Convenience
----------------------------

If you prefer, you can extend your test classes from ``VarnishTestCase``,
``NginxTestCase`` or ``SymfonyTestCase``. The appropriate traits will then
automatically be included.

Usage
-----

This example shows how you can test whether the caching headers your
application sets influence your proxy server as you expect them to::

    use FOS\HttpCache\Test\CacheAssertions;
    use FOS\HttpCache\Test\HttpCaller;
    use FOS\HttpCache\Test\VarnishTest;
    // or FOS\HttpCache\Test\NginxTest;
    // or FOS\HttpCache\Test\SymfonyTest;
    use PHPUnit\Framework\TestCase;

    class YourTest extends TestCase
    {
        public function testCachingHeaders()
        {
            // The proxy server is (re)started, so you don’t have to worry
            // about  previously cached content. Before continuing, the
            // VarnishTest/ NginxTest trait waits for the proxy server to
            // become available.

            // Retrieve a URL from your application
            $response = $this->getResponse('/your/resource');

            // Assert the response was a cache miss (came from the backend
            // application)
            $this->assertMiss($response);

            // Assume the URL /your/resource sets caching headers. If we
            // retrieve it again, we should have a cache hit (response delivered
            // by the proxy server):
            $response = $this->getResponse('/your/resource');
            $this->assertHit($response);
        }
    }

This example shows how you can test whether your application purges content
correctly::

    use FOS\HttpCache\Test\CacheAssertions;
    use FOS\HttpCache\Test\HttpCaller;
    use FOS\HttpCache\Test\VarnishTest;
    // or FOS\HttpCache\Test\NginxTest;
    // or FOS\HttpCache\Test\SymfonyTest;
    use PHPUnit\Framework\TestCase;

    class YourTest extends TestCase
    {
        public function testCachePurge()
        {
            // Again, the proxy server is restarted, so your test is independent
            // from other tests

            $url = '/blog/articles/1';

            // First request must be a cache miss
            $this->assertMiss($this->getResponse($url));

            // Next requests must be a hit
            $this->assertHit($this->getResponse($url));

            // Purge
            $this->getProxyClient()->purge('/blog/articles/1');

            // First request after must again be a miss
            $this->assertMiss($this->getResponse($url));
        }
    }

For more ideas, see this library’s functional tests in the
:source:`tests/Functional/` directory.
