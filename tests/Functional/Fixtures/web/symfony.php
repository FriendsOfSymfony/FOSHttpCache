<?php

use FOS\HttpCache\SymfonyCache\PurgeSubscriber;
use FOS\HttpCache\SymfonyCache\RefreshSubscriber;
use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use FOS\HttpCache\Test\Proxy\SymfonyProxy;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppCache;
use FOS\HttpCache\Tests\Functional\Fixtures\Symfony\AppKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\Store;

$loader = require_once __DIR__.'/../../../../vendor/autoload.php';

$symfonyProxy = new SymfonyProxy();

$kernel = new AppKernel();
$kernel = new AppCache($kernel, new Store($symfonyProxy->getCacheDir()), null, array('debug' => true));
$kernel->addSubscriber(new PurgeSubscriber(array('purge_method' => 'NOTIFY')));
$kernel->addSubscriber(new RefreshSubscriber());
$kernel->addSubscriber(new UserContextSubscriber());
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
