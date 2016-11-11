<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$tagHeader = empty($_GET['tags_header']) ? 'X-Cache-Tags' : $_GET['tags_header'];

header('Cache-Control: max-age=3600');
header('Content-Type: text/html');
if ($tagHeader === 'xkey') {
    header($tagHeader . ': tag1 tag2');
} else {
    header($tagHeader . ': tag1,tag2');
}
header('X-Cache-Debug: 1');
