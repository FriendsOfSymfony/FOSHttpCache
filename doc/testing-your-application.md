Testing your application
========================

This chapter describes how to test your application against a Varnish instance.

* [VarnishTestCase](#varnishtestcase)
* [Configuration](#configuration)
  * [Setting constants](#setting-constants)
  * [Overriding getters](#overriding-getters)
  * [Enable assertions](#enable-assertions)
* [Usage](#usage)

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
  `$this->getClient()` and `$this->getResponse()`
* custom assertions `assertHit` and `assertMiss` for validating a cache hit/miss.

Configuration
-------------

By default, the `VarnishTestCase` starts and stops a Varnish server for you.
All you have to do, is to refer the class to your Varnish configuration (VCL)
file. The recommended way to configure the test case is by setting constants
in your `phpunit.xml`. Alternatively, you can override the getter methods:


| Constant             | Getter                 | Default    | Description                                 |
| -------------------- | ---------------------- | ---------- | ------------------------------------------- |
| `VARNISH_FILE`       | `getConfigFile()`      |            | your Varnish configuration (VCL) file       |
| `VARNISH_BINARY`     | `getBinary()`          | `varnishd` | your Varnish binary                         |
| `VARNISH_PORT`       | `getVarnishPort()`     | `6181`     | port Varnish listens on                     |
| `VARNISH_MGMT_PORT`  | `getVarnishMgmtPort()` | `6182`     | Varnish management port                     |
| `WEB_SERVER_HOSTNAME`| `getHostName()`        |            | hostname your application can be reached at |

### Setting constants

Compare this library’s configuration to see how the constants are set:

```xml
<?xml version="1.0" encoding="UTF-8"?>

<phpunit ...>
    <php>
        <const name="VARNISH_FILE" value="./tests/FOS/HttpCache/Tests/Functional/Fixtures/varnish/fos.vcl" />
        <const name="WEB_SERVER_HOSTNAME" value="localhost" />
    </php>
</phpunit>
```

### Overriding getters

You can override getters in your test class in the following way.

```php
use FOS\HttpCache\Tests\VarnishTestCase;

class YourFunctionalTest extends VarnishTestCase
{
    protected function getVarnishPort()
    {
        return 8000;
    }
}
```

### Enable assertions

For the `assertHit` and `assertMiss` assertions to work, you should add a
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
