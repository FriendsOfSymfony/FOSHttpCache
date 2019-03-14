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
use PHPUnit\Framework\TestCase;

class VarnishProxyTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot find config file: nope.vcl
     */
    public function testInvalidConfigFileThrowsException()
    {
        new VarnishProxy('nope.vcl');
    }

    public function allowInlineFlagProvider()
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider allowInlineFlagProvider
     */
    public function testStart($inlineC)
    {
        $proxy = new VarnishProxyMock('config.vcl');
        $proxy->setBinary('/usr/sbin/varnishd');
        $proxy->setConfigDir('/my/varnish/dir');
        $proxy->setIp('192.168.0.1');
        $proxy->setManagementPort(1331);
        $proxy->setCacheDir('/tmp/cache/dir');
        $proxy->setAllowInlineC($inlineC);
        $this->assertEquals($inlineC, $proxy->getAllowInlineC());
        $proxy->start();

        $command = [
            '/usr/sbin/varnishd',
            '-a', '192.168.0.1:6181',
            '-T', '192.168.0.1:1331',
            '-f', 'config.vcl',
            '-n', '/tmp/cache/dir',
            '-p', 'vcl_dir=/my/varnish/dir',
            '-P', '/tmp/foshttpcache-varnish.pid',
        ];
        if ($inlineC) {
            $command[] = '-p';
            $command[] = 'vcc_allow_inline_c=on';
        }

        $this->assertEquals($command, $proxy->command);
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

    public $wait = true;

    public function setConfigFile($configFile)
    {
        $this->configFile = $configFile;
    }

    protected function runCommand(array $command, $sudo = false)
    {
        $this->command = $command;
    }

    protected function wait($timeout, $callback)
    {
        return $this->wait;
    }
}
