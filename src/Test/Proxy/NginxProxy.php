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

class NginxProxy extends Abstractproxy
{
    protected $binary = 'nginx';
    protected $configFile;
    protected $port = 8080;
    protected $pid = '/tmp/foshttpcache-nginx.pid';
    protected $cacheDir;

    /**
     * Constructor
     *
     * @param string $configFile Path to NGINX configuration file
     */
    public function __construct($configFile)
    {
        $this->setConfigFile($configFile);
        $this->setCacheDir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'foshttpcache-nginx');
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $this->runCommand(
            $this->getBinary(),
            array(
                '-c', $this->getConfigFile() ,
                '-g', 'pid ' . $this->pid . ';'
            )
        );

        $this->waitFor($this->getIp(), $this->getPort(), 2000);
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        if (file_exists($this->pid)) {
            $this->runCommand('kill', array(file_get_contents($this->pid)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->runCommand('rm', array('-rf', $this->getCacheDir()));
        $this->start();
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
