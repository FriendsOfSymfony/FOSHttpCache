.. _testing your application:

Testing Your Application
========================

This chapter describes how to test your application against a Varnish instance.

VarnishTestCase
---------------

The FOSHttpCache library provides a base test case to help you write functional
tests against a Varnish instance. This may be helpful to test the way your
application sets caching headers and invalidates cached content.

By having your test classes extend ``VarnishTestCase``, you get:

* independent tests: all previously cached content is removed by restarting
  Varnish in the test setUp method
* ``$this->varnish`` referring to an instance of this library’s Varnish client
  that is configured to talk to your Varnish server
* convenience methods for executing HTTP requests to your application:
  ``$this->getHttpClient()`` and ``$this->getResponse()``
* custom assertions ``assertHit`` and ``assertMiss`` for validating a cache hit/miss.

Configuration
-------------

By default, the ``VarnishTestCase`` starts and stops a Varnish server for you.
All you have to do, is to refer the class to your Varnish configuration (VCL)
file. The recommended way to configure the test case is by setting constants
in your `phpunit.xml`. Alternatively, you can override the getter methods:

======================= ========================= ============ ==========================================
Constant                Getter                    Default      Description
======================= ========================= ============ ==========================================
``VARNISH_FILE``        ``getConfigFile()``                    your Varnish configuration (VCL) file
``VARNISH_BINARY``      ``getBinary()``           ``varnishd`` your Varnish binary
``VARNISH_PORT``        ``getCachingProxyPort()`` ``6181``     port Varnish listens on
``VARNISH_MGMT_PORT``   ``getVarnishMgmtPort()``  ``6182``     Varnish management port
``WEB_SERVER_HOSTNAME`` ``getHostName()``                      hostname your application can be reached at
======================= ========================= ============ ==========================================

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

Enable Assertions
~~~~~~~~~~~~~~~~~

For the `assertHit` and `assertMiss` assertions to work, you should add a
:ref:`custom X-Debug header <varnish_debugging>` to responses served
by your Varnish.

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


For more ideas, see this library’s functional tests in the
:source:`tests/Functional/` directory.

