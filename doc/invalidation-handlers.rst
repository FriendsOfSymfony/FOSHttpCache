Extra Invalidation Handlers
===========================

This library provides decorators that build on top of the ``CacheInvalidator``
to simplify common operations.

.. _tags:

Tag Handler
-----------

.. versionadded:: 1.3
    The tag handler was added in FOSHttpCache 1.3. If you are using an older
    version of the library and can not update, you need to use
    ``CacheInvalidator::invalidateTags``.

The tag handler helps you to mark responses with tags that you can later use to
invalidate all cache entries with that tag. Tag invalidation works only with a
``CacheInvalidator`` that supports ``CacheInvalidator::INVALIDATE``.

Setup
~~~~~

.. note::

    Make sure to :doc:`configure your proxy <proxy-configuration>` for tagging first.

The tag handler is a decorator around the ``CacheInvalidator``. After
:doc:`creating the invalidator <cache-invalidator>` with a proxy client
that implements the ``BanInterface``, instantiate the ``TagHandler``::

    use FOS\HttpCache\Handler\TagHandler;

    // $cacheInvalidator already created as instance of FOS\HttpCache\CacheInvalidator
    $tagHandler = new TagHandler($cacheInvalidator);

Usage
~~~~~

With tags you can group related representations so it becomes easier to
invalidate them. You will have to make sure your web application adds the
correct tags on all responses. You can add tags to the handler using::

    $tagHandler->addTags(array('tag-two', 'group-a'));

Before any content is sent out, you need to send the tag header_::

    header(sprintf('%s: %s'),
        $tagHandler->getTagsHeaderName(),
        $tagHandler->getTagsHeaderValue()
    );

.. tip::

    If you are using Symfony with the FOSHttpCacheBundle_, the tag header is
    set automatically. You also have `additional methods of defining tags`_ with
    annotations and on URL patterns.

Assume you sent four responses:

+------------+-------------------------+
| Response:  | ``X-Cache-Tags`` header:|
+============+=========================+
| ``/one``   | ``tag-one``             |
+------------+-------------------------+
| ``/two``   | ``tag-two, group-a``    |
+------------+-------------------------+
| ``/three`` | ``tag-three, group-a``  |
+------------+-------------------------+
| ``/four``  | ``tag-four, group-b``   |
+------------+-------------------------+

You can now invalidate some URLs using tags::

    $tagHandler->invalidateTags(array('group-a', 'tag-four'))->flush();

This will ban all requests having either the tag ``group-a`` /or/ ``tag-four``.
In the above example, this will invalidate ``/two``, ``/three`` and ``/four``.
Only ``/one`` will stay in the cache.

.. _custom_tags_header:

Custom Tags Header
~~~~~~~~~~~~~~~~~~

Tagging uses a custom HTTP header to identify tags. You can change the default
header ``X-Cache-Tags`` in the constructor::

    use FOS\HttpCache\Handler\TagHandler;

    // $cacheInvalidator already created as instance of FOS\HttpCache\CacheInvalidator
    $tagHandler = new TagHandler($cacheInvalidator, 'My-Cache-Header');

Make sure to reflect this change in your
:doc:`caching proxy configuration <proxy-configuration>`.

.. _header: http://php.net/header
.. _additional methods of defining tags: http://foshttpcachebundle.readthedocs.org/en/latest/features/tagging.html
