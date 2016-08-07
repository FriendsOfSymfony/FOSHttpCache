<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

header('Cache-Debug: 1');

if (isset($_GET['accept'])) {
    if ($_GET['accept'] != $_SERVER['HTTP_ACCEPT']) {
        header('HTTP/1.1 500 Wrong accept header "'.$_SERVER['HTTP_ACCEPT'].'", expected "'.$_GET['accept'].'"');
        exit;
    }
} elseif (isset($_SERVER['HTTP_ACCEPT'])) {
    header('HTTP/1.1 500 Expected no accept header '.$_SERVER['HTTP_ACCEPT']);
    exit;
}

if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'])) {
    echo 'POST';
    exit;
}

if (!isset($_COOKIE[0]) || ($_COOKIE[0] !== "foo" && $_COOKIE[0] !== "bar")) {
    header('HTTP/1.1 403');
    exit;
}

header('Cache-Control: max-age=3600');
header('Vary: User-Context-Hash');

if ($_COOKIE[0] === "foo") {
    header('HashTest: foo');
    echo "foo";
} else {
    header('HashTest: bar');
    echo "bar";
}
