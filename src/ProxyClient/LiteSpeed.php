<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient;

use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;

/**
 * LiteSpeed Web Server (LSWS) invalidator.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class LiteSpeed extends HttpProxyClient implements PurgeCapable, TagCapable, ClearCapable
{
    private $headerLines = [];

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->addHeaderLine('X-LiteSpeed-Purge', '*');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $urlParts = parse_url($url);
        $host = null;

        if (isset($urlParts['host'])) {
            $host = $urlParts['host'];
            $url = $urlParts['path'];
        }

        $this->addHeaderLine('X-LiteSpeed-Purge', $url, $host);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();

        $resolver->setRequired(['document_root']);
        $resolver->setDefaults([
            'target_dir' => '',
            'base_uri' => '/',
        ]);

        $resolver->setAllowedTypes('document_root', 'string');
        $resolver->setAllowedTypes('target_dir', 'string');
        $resolver->setAllowedTypes('base_uri', 'string');

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $this->addHeaderLine('X-LiteSpeed-Purge', implode(', ', preg_filter('/^/', 'tag=', $tags)));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $filenames = [];

        $url = '/';

        if ($this->options['target_dir']) {
            $url .= $this->options['target_dir'].'/';
        }

        foreach ($this->headerLines as $host => $lines) {
            $filename = $this->createFileForHost($host);

            $this->queueRequest('GET', $url.$filename, []);

            $filenames[] = $filename;
        }

        try {
            return parent::flush();
        } finally {
            // Reset
            $this->headerLines = [];

            foreach ($filenames as $filename) {
                unlink($this->getFilePath().'/'.$filename);
            }
        }
    }

    private function addHeaderLine($header, $value, $host = null)
    {
        if (null === $host) {
            $host = $this->options['base_uri'];
        }

        if (!isset($this->headerLines[$host])) {
            $this->headerLines[$host] = [];
        }

        $this->headerLines[$host][] = $header.': '.$value;
    }

    /**
     * Creates the file and returns the file name.
     *
     * @param string $host
     *
     * @return string
     */
    private function createFileForHost($host)
    {
        $content = '<?php'."\n\n";

        foreach ($this->headerLines[$host] as $header) {
            $content .= sprintf('header(\'%s\');', addslashes($header))."\n";
        }

        // Generate a reasonably random file name, no need to be cryptographically safe here
        $filename = 'fos_cache_litespeed_purger_'.substr(sha1(uniqid('', true).mt_rand()), 0, mt_rand(10, 40)).'.php';

        file_put_contents($this->getFilePath().'/'.$filename, $content);

        return $filename;
    }

    /**
     * @return string
     */
    private function getFilePath()
    {
        $path = $this->options['document_root'];

        if ($this->options['target_dir']) {
            $path .= '/'.$this->options['target_dir'];
        }

        return $path;
    }
}
