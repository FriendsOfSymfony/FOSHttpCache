Testing the library
===================

This chapter describes how to run the tests that are included with this library.

Unit tests
----------

To run this library’s unit tests: clone the repository, install its vendors and
invoice PHPUnit:

```bash
$ git clone https://github.com/ddeboer/FOSHttpCache.git
$ composer install --dev
$ phpunit --testsuite unit
```

Functional tests
----------------

The library also includes functional tests against a Varnish instance. The
functional test suite by default uses PHP’s built-in web server. If you have
PHP 5.4 or newer, simply run with the default configuration.

If you want to run the tests on PHP 5.3, you need to configure a web server
listening on localhost:8080 that points to the folder
`tests/FOS/HttpCache/Tests/Functional/Fixtures/web`.

Run the functional tests:

```bash
$ git clone https://github.com/ddeboer/FOSHttpCache.git
$ composer install --dev
$ phpunit --testsuite functional
```

For more information about testing, see the [Testing your application](testing-your-application.md)
chapter.