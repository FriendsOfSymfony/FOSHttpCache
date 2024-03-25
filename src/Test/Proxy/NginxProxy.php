<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test\Proxy;

class NginxProxy extends AbstractProxy
{
    protected string $binary = 'nginx';

    protected string $configFile;

    protected int $port = 8080;

    protected string $pid = '/tmp/foshttpcache-nginx.pid';

    protected string $cacheDir;

    public function __construct(string $configFile)
    {
        $this->setConfigFile($configFile);
        $this->setCacheDir(sys_get_temp_dir().DIRECTORY_SEPARATOR.'foshttpcache-nginx');
    }

    public function start(): void
    {
        $this->runCommand(
            $this->getBinary(),
            [
                '-c', $this->getConfigFile(),
                '-g', 'pid '.$this->pid.';',
            ]
        );

        $this->waitFor($this->getIp(), $this->getPort(), 2000);
    }

    public function stop(): void
    {
        if (file_exists($this->pid)) {
            $this->runCommand('kill', [trim(file_get_contents($this->pid))]);
        }
    }

    public function clear(): void
    {
        $this->runCommand('rm', ['-rf', $this->getCacheDir()]);
        $this->start();
    }

    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }
}
