.. _testing your application:

Testing Your Application
========================

This chapter describes how to test your application against your reverse proxy.

The FOSHttpCache library provides base test case classes to help you write
functional tests. This is helpful to test the way your application sets caching
headers and invalidates cached content.

By having your test classes extend one of the test case classes, you get:

* independent tests: all previously cached content is removed in the tests
  ``setUp`` method. The way this is done depends on which reverse proxy you use;
* an instance of this library’s client that is configured to talk to your
  reverse proxy server. See reverse proxy specific sections for details;
* convenience methods for executing HTTP requests to your application:
  ``$this->getHttpClient()`` and ``$this->getResponse()``;
* custom assertions ``assertHit`` and ``assertMiss`` for validating a cache
  hit/miss.

The recommended way to configure the test case is by setting constants
in your ``phpunit.xml``. Alternatively, you can override the getter methods.

You will need to run a web server to provide the PHP application you want to
test. The test cases only handle running the caching proxy. With PHP 5.4 or
newer, the easiest is to use the PHP built in web server. See the
``WebServerListener`` class in ``tests/Functional`` and how it is registered in
``phpunit.xml.dist``.

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
Make sure ``symfony/process`` is available in your project:

.. code-block:: bash

    $ composer require symfony/process

Then set your Varnish configuration (VCL) file. All available configuration
parameters are shown below.

======================= ========================= ================================================== ===========================================
Constant                Getter                    Default                                            Description
======================= ========================= ================================================== ===========================================
``VARNISH_FILE``        ``getConfigFile()``                                                          your Varnish configuration (VCL) file
``VARNISH_BINARY``      ``getBinary()``           ``varnishd``                                       your Varnish binary
``VARNISH_PORT``        ``getCachingProxyPort()`` ``6181``                                           port Varnish listens on
``VARNISH_MGMT_PORT``   ``getVarnishMgmtPort()``  ``6182``                                           Varnish management port
``VARNISH_CACHE_DIR``   ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-varnish`` directory to use for cache
``VARNISH_VERSION``     ``getVarnishVersion()``   ``3``                                              installed varnish application version
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                            hostname your application can be reached at
======================= ========================= ================================================== ===========================================

Enable Assertions
'''''''''''''''''

For the `assertHit` and `assertMiss` assertions to work, you need to add a
:ref:`custom X-Cache header <varnish_debugging>` to responses served
by your Varnish.

NginxTestCase
-------------

Configuration
'''''''''''''

By default, the ``NginxTestCase`` starts and stops the NGINX server for you and
deletes all cached contents. Make sure ``symfony/process`` is available in your
project:

.. code-block:: bash

    $ composer require symfony/process

You have to set your NGINX configuration file. All available configuration
parameters are shown below.

======================= ========================= ================================================ ===========================================
Constant                Getter                    Default                                          Description
======================= ========================= ================================================ ===========================================
``NGINX_FILE``          ``getConfigFile()``                                                        your NGINX configuration file
``NGINX_BINARY``        ``getBinary()``           ``nginx``                                        your NGINX binary
``NGINX_PORT``          ``getCachingProxyPort()`` ``8088``                                         port NGINX listens on
``NGINX_CACHE_PATH``    ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-nginx`` directory to use for cache
                                                                                                   Must match `proxy_cache_path` directive in
                                                                                                   your configuration file.
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                          hostname your application can be reached at
======================= ========================= ================================================ ===========================================

Enable Assertions
'''''''''''''''''

For the `assertHit` and `assertMiss` assertions to work, you need to add a
:ref:`custom X-Cache header <nginx_debugging>` to responses served
by your Nginx.

SymfonyTestCase
---------------

This test case helps to test invalidation requests with a symfony application
running the Symfony HttpCache and invalidating its cache folder to get reliable
tests.

The ``SymfonyTestCase`` does automatically start a web server. It is assumed
that the web server you run for the application has the HttpCache integrated.

Configuration
'''''''''''''

======================= ========================= ================================================ ===========================================
Constant                Getter                    Default                                          Description
======================= ========================= ================================================ ===========================================
``WEB_SERVER_HOSTNAME`` ``getHostName()``                                                          hostname your application can be reached at
``WEB_SERVER_PORT``     ``getConfigFile()``                                                        The port on which the web server runs
``SYMFONY_CACHE_DIR``   ``getCacheDir()``         ``sys_get_temp_dir()`` + ``/foshttpcache-nginx`` directory to use for cache
                                                                                                   Must match the configuration of your
                                                                                                   HttpCache and must be writable by the user
                                                                                                   running PHPUnit.
======================= ========================= ================================================ ===========================================

Enable Assertions
'''''''''''''''''

For the `assertHit` and `assertMiss` assertions to work, you need to add debug
information in your AppCache. Create the cache kernel with the option
``'debug' => true`` and add the following to your ``AppCache``::

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = parent::handle($request, $type, $catch);

        if ($response->headers->has('X-Symfony-Cache')) {
            if (false !== strpos($response->headers->get('X-Symfony-Cache'), 'miss')) {
                $state = 'MISS';
            } elseif (false !== strpos($response->headers->get('X-Symfony-Cache'), 'fresh')) {
                $state = 'HIT';
            } else {
                $state = 'UNDETERMINED';
            }
            $response->headers->set('X-Cache', $state);
        }

        return $response;
    }

The ``UNDETERMINED`` state should never happen. If it does, it means that your
HttpCache is not correctly set into debug mode.

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
