<?php

namespace FOS\HttpCache\Tests\Functional\Proxy;

interface ProxyInterface
{
    public function start();
    public function stop();
    public function clear();
}
