Contributing
============

We warmly welcome contributions to FOSHttpCache. Before you invest a lot of time
however, please open an issue on GitHub_ to discuss your idea. Then we can
coordinate efforts if somebody is already working on the same thing.

Testing the Library
-------------------

This chapter describes how to run the tests that are included with this library.
Make sure that you have PHPUnit 5.7 or higher installed.

First clone the repository, install the vendors, then run the tests:

.. code-block:: bash

    $ git clone https://github.com/FriendsOfSymfony/FOSHttpCache.git
    $ cd FOSHttpCache
    $ composer install --dev
    $ phpunit

Unit Tests
~~~~~~~~~~

To run the unit tests separately:

.. code-block:: bash

    $ phpunit tests/Unit

Functional Tests
~~~~~~~~~~~~~~~~

The library also includes functional tests against a Varnish and NGINX instance.
The functional test suite uses PHP’s built-in web server by default. To run the
functional tests:

.. code-block:: bash

    $ phpunit tests/Functional

Tests are organized in groups: one for each reverse proxy supported. At the moment
groups are: `varnish` and  `nginx`.

To run only the `varnish` functional tests:

.. code-block:: bash

    $ phpunit --group=varnish

For more information about testing, see :doc:`/testing-your-application`.

Building the Documentation
--------------------------

First `install Sphinx`_ and `install enchant`_ (e.g. ``sudo apt-get install enchant``),
then download the requirements:

.. code-block:: bash

    $ pip install -r doc/requirements.txt

To build the docs:

.. code-block:: bash

    $ cd doc
    $ make html
    $ make spelling

.. _install Sphinx: http://sphinx-doc.org/latest/install.html
.. _install enchant: http://www.abisource.com/projects/enchant/
