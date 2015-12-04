<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

header('Cache-Control: s-maxage=0');
header('X-Reverse-Proxy-TTL: 3600');
header('Content-Type: text/html');
header('X-Cache-Debug: 1');

echo microtime(true);
