<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

header('Cache-Control: public, max-age=2, s-maxage=3600');
header('Age: 0');
header('Date: '.gmdate('D, d M Y H:i:s \G\M\T', time()));
header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', time()));
header('Content-Type: text/html');
header('X-Cache-Debug: 1');

echo microtime(true);
