<?php

namespace FOS\HttpCache\Test\Proxy;

class NginxProxy extends Abstractproxy
{
    protected $binary = 'nginx';
    protected $configFile;
    protected $port = 8080;
    protected $pid = '/tmp/foshttpcache-nginx.pid';
    protected $cacheDir;

    public function __construct($configFile)
    {
        $this->setConfigFile($configFile);
    }

    /**
     * Start the proxy server
     */
    public function start()
    {
        $this->runCommand(
            $this->getBinary(),
            array(
                ' -c ' . $this->getConfigFile() .
                ' -g "pid ' . $this->pid . ';"'
            )
        );

        $this->waitFor('127.0.0.1', $this->getPort(), 2000);
    }

    /**
     * Stop the proxy server
     */
    public function stop()
    {
        if (file_exists($this->pid)) {
            $this->runCommand('kill', array(file_get_contents($this->pid)));
        }
    }

    /**
     * Clear all cached content from the proxy server
     */
    public function clear()
    {
        $this->runCommand('rm', array('-rf', $this->getCacheDir() . '*'));
    }

    /**
     * @param string $configFile
     *
     * @throws \InvalidArgumentException
     */
    public function setConfigFile($configFile)
    {
        if (!file_exists($configFile)) {

            throw new \InvalidArgumentException('Can not find specified nginx config file: ' . $configFile);
        }

        $this->configFile = $configFile;
    }

    /**
     * @return mixed
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * @param mixed $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return mixed
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }
}
