Changelog
=========

See also the [GitHub releases page](https://github.com/FriendsOfSymfony/FOSHttpCache/releases).

1.4.0
-----

* Added symfony/http-kernel [HttpCache client](http://foshttpcache.readthedocs.org/en/latest/proxy-clients.html#symfony-client).
* Added [SymfonyTestCase](http://foshttpcache.readthedocs.org/en/latest/testing-your-application.html#symfonytestcase). 
* Removed unneeded files from dist packages.

1.3.2
-----

* Added `TagHandler->hasTags()` method.

1.3.1
-----

* Added authentication support to user context subscribe.
* Fixed usage of deprecated Guzzle subtree splits. 
* Fixed exposed cache tags.

1.3.0
-----

* Added [TagHandler](http://foshttpcache.readthedocs.org/en/latest/invalidation-handlers.html#tag-handler).
* It is no longer possible to change the event dispatcher of the
  CacheInvalidator once its instantiated. If you need a custom dispatcher, set 
  it right after creating the invalidator instance. 
* Deprecated `CacheInvalidator::addSubscriber` in favor of either using the event 
  dispatcher instance you inject or doing `getEventDispatcher()->addSubscriber($subscriber)`.

1.2.0
-----

* Added support for the symfony/http-kernel component reverse proxy HttpCache.

1.1.2
-----

* Fixed documentation for user context varnish configuration to also work when
  client omits the `Accept` HTTP header.
