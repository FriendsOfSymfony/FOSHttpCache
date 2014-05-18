<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException("Install dependencies using composer to run the test suite.");
}

if (!defined('VARNISH_FILE')) {
    if (getenv('VARNISH_VERSION')
        && (0 === strncmp('4.', getenv('VARNISH_VERSION'), 2))
    ) {
        define('VARNISH_FILE', './tests/Functional/Fixtures/varnish-4/fos.vcl');
    } else {
        define('VARNISH_FILE', './tests/Functional/Fixtures/varnish-3/fos.vcl');
    }
}

$autoload = require_once $file;
