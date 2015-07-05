<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\Test\Proxy;

use FOS\HttpCache\Test\Proxy\VarnishProxy;

class VarnishProxyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot find config file: nope.vcl
     */
    public function testInvalidConfigFileThrowsException()
    {
        new VarnishProxy('nope.vcl');
    }

    public function testStart()
    {
        $proxy = new VarnishProxyMock('config.vcl');
        $proxy->setBinary('/usr/sbin/varnishd');
        $proxy->setConfigDir('/my/varnish/dir');
        $proxy->setIp('192.168.0.1');
        $proxy->setManagementPort(1331);
        $proxy->setCacheDir('/tmp/cache/dir');
        $proxy->start();

        $this->assertEquals('/usr/sbin/varnishd', $proxy->command);
        $this->assertEquals(
            [
                '-a', '192.168.0.1:6181',
                '-T', '192.168.0.1:1331',
                '-f', 'config.vcl',
                '-n', '/tmp/cache/dir',
                '-p', 'vcl_dir=/my/varnish/dir',
                '-S', realpath('./tests/Functional/Fixtures/secret'),
                '-P', '/tmp/foshttpcache-varnish.pid',
            ],
            $proxy->arguments
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Caching proxy cannot be reached at 127.0.0.1:6181
     */
    public function testWaitThrowsException()
    {
        $proxy = new VarnishProxyMock('config.vcl');
        $proxy->wait = false;

        $proxy->start();
    }
}

class VarnishProxyMock extends VarnishProxy
{
    public $command;
    public $arguments;
    public $wait = true;

    public function setConfigFile($configFile)
    {
        $this->configFile = $configFile;
    }

    protected function runCommand($command, array $arguments)
    {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    protected function wait($timeout, $callback)
    {
        return $this->wait;
    }
}
