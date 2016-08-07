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
header('Content-Type: text/html');
header('Cache-Tags: tag1,tag2');
header('Cache-Debug: 1');
