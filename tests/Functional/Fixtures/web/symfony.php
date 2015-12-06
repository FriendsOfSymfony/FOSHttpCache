<?php

use FOS\HttpCache\Test\Proxy\SymfonyProxy;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppCache;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\Store;
use FOS\HttpCache\SymfonyCache\Tag\NullManager;

$loader = require_once __DIR__.'/../../../../vendor/autoload.php';

$symfonyProxy = new SymfonyProxy();

$httpCacheStore = new Store($symfonyProxy->getCacheDir();
$tagStore = new DoctrineCachTagStore();
$sfHttpCacheTagManager = new SymfonyHttpCacheManager($tagStore, $httpCacheStore); // ...

$kernel = new AppKernel();
$kernel = new AppCache($kernel, $httpCacheStore), null, $sfHttpCacheTagManager, ['debug' => true]);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
