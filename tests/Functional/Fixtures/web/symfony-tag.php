<?php

use FOS\HttpCache\Test\Proxy\SymfonyProxy;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppCache;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\Store;

$loader = require_once __DIR__.'/../../../../vendor/autoload.php';

$symfonyProxy = new SymfonyProxy();

$kernel = new AppKernel();
$kernel = new EventDispa
$kernel = new AppCache($kernel, new Store($symfonyProxy->getCacheDir()), null, ['debug' => true]);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
