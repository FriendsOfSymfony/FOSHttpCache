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
    echo "anonymous";
} elseif ($_COOKIE[0] == "foo") {
    header('X-HashTest: foo');
    echo "foo";
} else {
    header('X-HashTest: bar');
    echo "bar";
}
