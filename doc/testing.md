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
functional test suite by default uses PHP’s built-in web server. If you have
PHP 5.4 or newer, simply run with the default configuration.

If you want to run the tests on PHP 5.3, you need to configure a webserver
listening on localhost:8080 that points to the folder
`tests/FOS/HttpCache/Tests/Functional/Fixtures/web`.

Run the functional tests:

```bash
$ phpunit --testsuite functional
```
