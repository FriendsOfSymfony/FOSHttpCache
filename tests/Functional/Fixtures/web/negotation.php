<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

header('Cache-Control: max-age=3600');
header(sprintf('Content-Type: %s', $_SERVER['HTTP_ACCEPT']));
header('X-Cache-Debug: 1');
header('Vary: Accept');
