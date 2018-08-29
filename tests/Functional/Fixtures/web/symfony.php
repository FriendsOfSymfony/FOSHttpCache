<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use FOS\HttpCache\Test\Proxy\SymfonyProxy;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppCache;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Toflar\Psr6HttpCacheStore\Psr6Store;

$loader = require_once __DIR__.'/../../../../vendor/autoload.php';

$symfonyProxy = new SymfonyProxy();

if (class_exists(Psr6Store::class)) {
    $store = new Psr6Store(['cache_directory' => $symfonyProxy->getCacheDir(), 'cache_tags_header' => 'X-Cache-Tags']);
} else {
    $store = new Store($symfonyProxy->getCacheDir());
}

$kernel = new AppKernel();
$kernel = new AppCache($kernel, $store, null, ['debug' => true]);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
