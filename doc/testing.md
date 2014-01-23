Testing
=======

In addition to being thoroughly tested, this library also provides a base test
case to help writing functional tests with varnish. Have a look at the
VarnishTestCase if you want to build your own tests.

* [Unit tests](#unit-tests)
* [Funtional tests](#functional-tests)

Unit tests
----------

Clone this repository, then install its vendors, and invoke PHPUnit:

```bash
$ composer install --dev
$ phpunit --testsuite unit
```

Functional tests
----------------

The library also includes functional tests against a Varnish instance. The
functional test suite by default uses PHP’s built-in web server. If you do not
have PHP 5.4 or higher, you will need to copy phpunit.xml.dist to phpunit.xml
and configure a web server pointing to the folder
tests/FOS/HttpCache/Tests/Functional/Fixtures/web

Start a Varnish server and run the functional tests:

```bash
$ phpunit --testsuite functional
```
