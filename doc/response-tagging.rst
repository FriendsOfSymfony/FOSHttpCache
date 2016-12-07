Response Tagging
================

The ``ResponseTagger`` helps you keep track tags for a response, which can be
added to response headers that you can later use to invalidate all cache
entries with that tag.

.. _tags:

Setup
~~~~~

.. note::

    Make sure to :doc:`configure your proxy <proxy-configuration>` for tagging first.

The response tagger is a decorator around a proxy client that implements
the ``TagCapable`` interface, handling adding tags to responses::

    use FOS\HttpCache\ResponseTagger;

    // $proxyClient already created, implementing FOS\HttpCache\ProxyClient\Invalidation\TagCapable
    $responseTagger = new ResponseTagger($proxyClient);

.. _response_tagger_optional_parameters:

The response tagger validates tags that you set. By default, it simply ignores
empty strings. You can set the response tagger to strict mode to have it throw
an ``InvalidTagException`` on empty tags::

    $responseTagger = new ResponseTagger($proxyClient, ['strict' => true]);

Usage
~~~~~

With tags you can group related representations so it becomes easier to
invalidate them. You will have to make sure your web application adds the
correct tags on all responses. You can add tags to the response using::

    $responseTagger->addTags(['tag-two', 'group-a']);

Before any content is sent out, you need to send the tag header_::

    header(sprintf('%s: %s',
        $responseTagger->getTagsHeaderName(),
        $responseTagger->getTagsHeaderValue()
    ));

.. tip::

    If you are using Symfony with the FOSHttpCacheBundle_, the tags
    added to ``ResponseTagger`` are added to the response automatically.
    You also have `additional methods of defining tags`_ with
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

    $tagHandler->invalidateTags(['group-a', 'tag-four'])->flush();

This will ban all requests having either the tag ``group-a`` /or/ ``tag-four``.
In the above example, this will invalidate ``/two``, ``/three`` and ``/four``.
Only ``/one`` will stay in the cache.

.. note::

    For further reading on tag invalidation see :doc:`cache-invalidator page <cache-invalidator>`.
    For changing the cache header, :doc:`configure your proxy <proxy-clients>`.

.. _header: http://php.net/header
.. _additional methods of defining tags: http://foshttpcachebundle.readthedocs.org/en/latest/features/tagging.html
