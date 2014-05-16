<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

header('X-Cache-Debug: 1');

// The application listens to HEAD requests and creates an X-FOSHttpCache-Hash based on
// parameters in the request. In this case it's based on Cookie.
if ('HEAD' == strtoupper($_SERVER['REQUEST_METHOD'])) {
    header(sprintf('X-FOSHttpCache-Hash: %s', $_COOKIE[0]));
    header('Vary: X-FOSHttpCache-SessionId');
    header('Cache-Control: max-age=3600');
    exit;
}
