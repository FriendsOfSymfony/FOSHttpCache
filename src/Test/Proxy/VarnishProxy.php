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

/**
 * {@inheritdoc}
 */
class VarnishProxy extends AbstractProxy
{
    protected $binary = 'varnishd';

    protected $port = 6181;

    protected $managementPort = 6182;

    protected $pid = '/tmp/foshttpcache-varnish.pid';

    protected $configFile;

    protected $configDir;

    protected $cacheDir;

    protected $allowInlineC = false;

    /**
     * Constructor.
     *
     * @param string $configFile Path to VCL file
     */
    public function __construct($configFile)
    {
        $this->setConfigFile($configFile);
        $this->setCacheDir(sys_get_temp_dir().DIRECTORY_SEPARATOR.'foshttpcache-varnish');
    }

    /**
     * {@inheritdoc}
     */
    public function start()
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

    /**
     * {@inheritdoc}
     */
    public function stop()
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

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->stop();
        $this->start();
    }

    /**
     * @param string $configDir
     */
    public function setConfigDir($configDir)
    {
        $this->configDir = $configDir;
    }

    /**
     * @return string
     */
    public function getConfigDir()
    {
        if (null === $this->configDir && null !== $this->configFile) {
            return dirname(realpath($this->getConfigFile()));
        }

        return $this->configDir;
    }

    /**
     * Set Varnish management port (defaults to 6182).
     *
     * @param int $managementPort
     */
    public function setManagementPort($managementPort)
    {
        $this->managementPort = $managementPort;
    }

    /**
     * Get Varnish management port.
     *
     * @return int
     */
    public function getManagementPort()
    {
        return $this->managementPort;
    }

    /**
     * Set Varnish cache directory.
     *
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Get Varnish cache directory.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Whether the inline C flag should be set.
     *
     * @return bool
     */
    public function getAllowInlineC()
    {
        return $this->allowInlineC;
    }

    /**
     * Set whether the inline c flag should be on or off.
     *
     * @param bool $allowInlineC True for on, false for off
     */
    public function setAllowInlineC($allowInlineC)
    {
        $this->allowInlineC = (bool) $allowInlineC;
    }

    /**
     * Defaults to 4.
     *
     * @return int
     */
    private function getVarnishVersion()
    {
        return getenv('VARNISH_VERSION') ?: '4.0';
    }
}
