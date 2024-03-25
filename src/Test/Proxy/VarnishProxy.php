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

class VarnishProxy extends AbstractProxy
{
    protected string $binary = 'varnishd';

    protected int $port = 6181;

    protected int $managementPort = 6182;

    protected string $pid = '/tmp/foshttpcache-varnish.pid';

    protected string $configFile;

    protected ?string $configDir = null;

    protected string $cacheDir;

    protected bool $allowInlineC = false;

    /**
     * @param string $configFile Path to VCL file
     */
    public function __construct(string $configFile)
    {
        $this->setConfigFile($configFile);
        $this->setCacheDir(sys_get_temp_dir().DIRECTORY_SEPARATOR.'foshttpcache-varnish');
    }

    public function start(): void
    {
        $vclPath = ((int) $this->getVarnishVersion()) >= 5 ? 'vcl_path' : 'vcl_dir';

        $args = [
            '-a', $this->ip.':'.$this->getPort(),
            '-T', $this->ip.':'.$this->getManagementPort(),
            '-f', $this->getConfigFile(),
            '-n', $this->getCacheDir(),
            '-p', $vclPath.'='.$this->getConfigDir(),

            '-P', $this->pid,
        ];
        if ($this->getAllowInlineC()) {
            $args[] = '-p';
            $args[] = 'vcc_allow_inline_c=on';
        }

        $this->runCommand($this->getBinary(), $args);

        $this->waitFor($this->ip, $this->getPort(), 5000);
    }

    public function stop(): void
    {
        if (file_exists($this->pid)) {
            try {
                $this->runCommand('kill', ['-9', trim(file_get_contents($this->pid))]);
            } catch (\RuntimeException $e) {
                // Ignore if command fails when Varnish wasn't running
            }
            unlink($this->pid);
            $this->waitUntil($this->ip, $this->getPort(), 8000);
        }
    }

    public function clear(): void
    {
        $this->stop();
        $this->start();
    }

    public function setConfigDir(string $configDir): void
    {
        $this->configDir = $configDir;
    }

    public function getConfigDir(): string
    {
        if (null === $this->configDir) {
            return dirname(realpath($this->getConfigFile()));
        }

        return $this->configDir;
    }

    /**
     * Set Varnish management port (defaults to 6182).
     */
    public function setManagementPort(int $managementPort): void
    {
        $this->managementPort = $managementPort;
    }

    public function getManagementPort(): int
    {
        return $this->managementPort;
    }

    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function getAllowInlineC(): bool
    {
        return $this->allowInlineC;
    }

    public function setAllowInlineC(bool $allowInlineC): void
    {
        $this->allowInlineC = (bool) $allowInlineC;
    }

    private function getVarnishVersion(): string
    {
        return getenv('VARNISH_VERSION') ?: '4.0';
    }
}
