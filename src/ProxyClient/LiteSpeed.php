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
        // Litespeed supports purging everything by passing *
        $this->addHeaderLine('X-LiteSpeed-Purge', '*');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $urlParts = parse_url($url);
        $host = array_key_exists('host', $urlParts) ? $urlParts['host'] : null;
        $url = array_key_exists('path', $urlParts) ? $urlParts['path'] : '/';

        $this->addHeaderLine('X-LiteSpeed-Purge', $url, $host);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * You must configure the following options:
     *
     * - document_root: Must contain the absolute path on your system, where the invalidation file should be created at.
     *
     * You can configure the following options:
     *
     * - target_dir: If you don't want to have your invalidation files reside in document_root directly, you can specify
     *               a target_dir. It will be appended to both, the document_root when creating the files and the URL
     *               when executing the invalidation request.
     * - base_uri:   The base_uri is used when you call purge() with passing an URL without any host (e.g. /path). The
     *               base_uri will be used as host then.
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

        $path = '/';

        if ($this->options['target_dir']) {
            $path .= $this->options['target_dir'].'/';
        }

        foreach ($this->headerLines as $host => $lines) {
            $filename = $this->createInvalidationFile($lines);

            $this->queueRequest('GET', $path.$filename);

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
     * @param array $lines
     *
     * @return string
     */
    private function createInvalidationFile(array $lines)
    {
        $content = '<?php'."\n\n";

        foreach ($lines as $header) {
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
