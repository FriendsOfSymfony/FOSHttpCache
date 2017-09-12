Response Tagging
================

The ``ResponseTagger`` helps you keep track of tags for a response. It can add
the tags as a response header that you can later use to invalidate all cache
entries with that tag.

.. _tags:

Setup
~~~~~

.. note::

    Make sure to :doc:`configure your proxy <proxy-configuration>` for tagging
    first.

The response tagger uses an instance of ``TagHeaderFormatter`` to know the
header name used to mark tags on the content and to format the tags into the
correct header value. This library ships with a
``CommaSeparatedTagHeaderFormatter`` that formats an array of tags into a
comma-separated list. What kind of format you need obviously depends on what
proxy you use and how this proxy is configured (if it even can be configured).
It is important you do understand that the ``ResponseTagger`` is not directly
linked to the proxy configuration so you are the one that has to make sure, they
match. Don't worry too much though, the default settings will work out of the box
and if you really need to adjust formatting, header names etc. you likely have
some special environment that needs special adjustments. So for now, just remember
you can configure all the stuff but you don't have to.

For example, the :doc:`default configuration of Varnish <varnish-configuration>` in
this documentation requires the header to contain a comma-separated list of tags
and the default examples use the header ``X-Cache-Tags``. If you don't change
the ``TagHeaderFormatter`` nor the header name, everything will be created
automatically and work out of the box for you::

    use FOS\HttpCache\ResponseTagger;

    $responseTagger = new ResponseTagger();

.. _response_tagger_optional_parameters:

If you need a different behavior, you can provide your own
``TagHeaderFormatter`` instance. Be aware that your
:doc:`proxy configuration <proxy-configuration>` has to match the response.
For example, to use a different header name::

    use FOS\HttpCache\ResponseTagger;
    use FOS\HttpCache\TagHeaderFormatter;

    $formatter = new CommaSeparatedTagHeaderFormatter('Custom-Header-Name');
    $responseTagger = new ResponseTagger(['header_formatter' => $formatter]);

The response tagger validates tags that you set. By default, it simply ignores
empty strings and does not add them to the list of tags. You can set the
response tagger to strict mode to have it throw an ``InvalidTagException`` on
empty tags::

    $responseTagger = new ResponseTagger(['strict' => true]);

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
