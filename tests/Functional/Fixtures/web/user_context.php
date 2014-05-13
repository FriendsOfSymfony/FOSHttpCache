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

if ('POST' == strtoupper($_SERVER['REQUEST_METHOD'])) {
    echo "POST";
    exit;
}

if (!isset($_COOKIE[0]) || ($_COOKIE[0] != "foo" && $_COOKIE[0] != "bar")) {
    header('HTTP/1.1 403');
    exit;
}

header('Cache-Control: max-age=3600');
header('Vary: x-user-context-hash');

if ($_COOKIE[0] == "foo") {
    header('X-HashTest: foo');
    echo "foo";
} else {
    header('X-HashTest: bar');
    echo "bar";
}
