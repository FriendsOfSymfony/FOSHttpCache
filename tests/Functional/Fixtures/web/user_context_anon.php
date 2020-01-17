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

header('Cache-Control: max-age=3600');
header('Vary: X-User-Context-Hash');

if (!isset($_COOKIE[0])) {
    header('X-HashTest: anonymous');
    header('Content-Length: 9');

    echo 'anonymous';
} elseif ('foo' === $_COOKIE[0]) {
    header('X-HashTest: foo');
    header('Content-Length: 3');

    echo 'foo';
} else {
    header('X-HashTest: bar');
    header('Content-Length: 3');

    echo 'bar';
}
