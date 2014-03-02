Testing the Library
===================

This chapter describes how to run the tests that are included with this library.

First clone the repository, install the vendors, then run the tests:

```bash
$ git clone https://github.com/FriendsOfSymfony/FOSHttpCache.git
$ cd FOSHttpCache
$ composer install --dev
$ phpunit
```

Unit Tests
----------

To run the unit tests separately:

```bash
$ phpunit tests/Unit
```

Functional Tests
----------------

The library also includes functional tests against a Varnish instance. The
functional test suite by default uses PHP’s built-in web server. If you have
PHP 5.4 or newer or [HHVM](http://www.hhvm.com/), simply run with the default
configuration.

If you want to run the tests on PHP 5.3, you need to configure a web server
listening on localhost:8080 that points to the folder
`tests/FOS/HttpCache/Tests/Functional/Fixtures/web`.

Run the functional tests:

```bash
$ phpunit tests/Functional
```

For more information about testing, see the
[Testing Your Application](testing-your-application.md) chapter.