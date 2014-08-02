<?php

namespace FOS\HttpCache\Tests\Functional\Proxy;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class VarnishProxy extends AbstractProxy
{
    private $binary = 'varnishd';
    private $port = 6181;
    private $managementPort = 6182;
    private $pid = '/tmp/foshttpcache-varnish.pid';
    private $configFile;
    private $configDir;
    private $cacheDir;

    public function __construct($configFile)
    {
        $this->cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'foshttpcache-test';

        $this->setConfigFile($configFile);
    }

    public function start()
    {
        $builder = new ProcessBuilder(array(
            $this->getBinary(),
            '-a', '127.0.0.1:' . $this->getPort(),
            '-T', '127.0.0.1:' . $this->getManagementPort(),
            '-f', $this->getConfigFile(),
            '-n', $this->getCacheDir(),
            '-p', 'vcl_dir=' . $this->getConfigDir(),
            '-P', $this->pid
        ));

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $this->waitFor('127.0.0.1', $this->getPort(), 2000);
    }

    public function stop()
    {
        if (file_exists($this->pid)) {
            $process = new Process('kill -9 ' . file_get_contents($this->pid));
            $process->run(); // Ignore if command fails when Varnish wasn't running
            unlink($this->pid);
            $this->waitUntil('127.0.0.1', $this->getPort(), 2000);
        }
    }

    public function clear()
    {
        $this->stop();
        $this->start();
    }

    /**
     * @param string $binary
     */
    public function setBinary($binary)
    {
        $this->binary = $binary;
    }

    /**
     * @return string
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * @param mixed $configDir
     */
    public function setConfigDir($configDir)
    {
        $this->configDir = $configDir;
    }

    /**
     * @return mixed
     */
    public function getConfigDir()
    {
        if (null === $this->configDir && null !== $this->configFile) {
            return dirname(realpath($this->getConfigFile()));
        }

        return $this->configDir;
    }

    /**
     * @param mixed $configFile
     * @throws \RuntimeException
     */

    public function setConfigFile($configFile)
    {
        if (!file_exists($configFile)) {
           throw new \RuntimeException('Cannot find config file: ' . $configFile);
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
     * @param int $managementPort
     */
    public function setManagementPort($managementPort)
    {
        $this->managementPort = $managementPort;
    }

    /**
     * @return int
     */
    public function getManagementPort()
    {
        return $this->managementPort;
    }

    /**
     * @param mixed $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }
}
