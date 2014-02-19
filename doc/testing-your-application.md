Testing your application
========================

This chapter describes how to test your application against a Varnish instance.

VarnishTestCase
---------------

The FOSHttpCache library provides a base test case to help you write functional
tests against a Varnish instance. This may be helpful to test the way your
application sets caching headers and invalidates cached content.

By having your test classes extend `VarnishTestCase`, you get:

* independent tests: all previously cached content is removed by restarting
  Varnish in the test setUp method
* `$this->varnish` referring to an instance of this library’s Varnish client
  that is configured to talk to your Varnish server
* convenience methods for executing HTTP requests to your application:
  `self::getClient()` and `$this->getResponse()`
* custom assertions `assertHit` and `assertMiss` for validating a cache hit/miss.

Configuration
-------------

By default, the `VarnishTestCase` starts and stops a Varnish server for you.
All you have to do, is to refer the class to your Varnish configuration (VCL)
file. You can can configure the test case in two ways: by overriding `setUp`
or by setting constants in your `phpunit.xml`.

### Using setUp

Override the test’s `setUp` method to set your VCL file:

```php
use FOS\HttpCache\Tests\VarnishTestCase;

class YourFunctionalTest extends VarnishTestCase
{
    public function setUp()
    {
        parent::setUp('/path/to/your/varnish-config.vcl');
    }
}
```

You can also override options by setting properties:

```php
class YourFunctionalTest extends VarnishTestCase
{
    protected static $binary     = '/your/varnish/binary';
    protected static $port       = 6181;
    protected static $mgmtPort   = 6182;
    protected static $configFile = '/your/varnish/config.vcl';
    protected static $cacheDir   = '/tmp/foshttpcache-test';
    protected static $hostName   = 'your-app.local';
}
```

### Using phpunit.xml

Another way to configure the test case, is by setting constants in your
`phpunit.xml` file.

* `VARNISH_FILE` points to your VCL file
* `VARNISH_BINARY` points to the Varnish binary (default `varnishd`).
* `VARNISH_PORT` is the port that Varnish listens on (default `6182`).
* `WEB_SERVER_HOSTNAME` is the hostname your application can be reached at
   through your web server.
* `WEB_SERVER_PORT` is your web server’s port number.
* `WEB_SERVER_DOCROOT` points to the directory that contains your application.

For an example as how to use the constants, have a look at this library’s
configuration:

```xml
<?xml version="1.0" encoding="UTF-8"?>

<phpunit ...>
    <php>
        <const name="VARNISH_FILE" value="./tests/FOS/HttpCache/Tests/Functional/Fixtures/varnish/fos.vcl" />
        <const name="WEB_SERVER_HOSTNAME" value="localhost" />
        <const name="WEB_SERVER_PORT" value="8080" />
        <const name="WEB_SERVER_DOCROOT" value="./tests/FOS/HttpCache/Tests/Functional/Fixtures/web" />
    </php>
</phpunit>
```

For the `assertHis` and `assertMiss` assertions to work, you should add a
custom `X-Debug` header to responses served by your Varnish. See
[this library’s VCL](../tests/FOS/HttpCache/Functional/Fixtures/Varnish/fos.vcl) for an example.

Usage
-----

This example shows how you can test whether the caching headers your
application sets influence Varnish as you expect them to.

```php
use FOS\HttpCache\Tests\VarnishTestCase;

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
```

This example shows how you can test whether your application purges content
correctly:

```php
    public function testCachePurge()
    {
        // Again, Varnish is restarted, so your test is independent

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
```



For more ideas, see this library’s [functional tests](../tests/FOS/HttpCache/Tests/Functional).
