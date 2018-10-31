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
        $this->headerLines[] = 'X-LiteSpeed-Purge: *';

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $this->headerLines[] = 'X-LiteSpeed-Purge: '.$url;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();

        $resolver->setRequired(['target_dir']);
        $resolver->setAllowedTypes('target_dir', 'string');

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $this->headerLines[] = 'X-LiteSpeed-Purge: '.implode(', ', preg_filter('/^/', 'tag=', $tags));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $filename = $this->createFile();

        $url = '/'.$filename;

        $this->queueRequest('GET', $url, []);

        $result = parent::flush();

        // Reset
        $this->headerLines = [];
        unlink($this->options['target_dir'].'/'.$filename);

        return $result;
    }

    /**
     * Creates the file and returns the file name.
     *
     * @return string
     */
    private function createFile()
    {
        $content = '<?php'."\n\n";

        foreach ($this->headerLines as $header) {
            $content .= sprintf('header(\'%s\');', $header)."\n";
        }

        $filename = $this->generateUrlSafeRandomFileName();

        file_put_contents($this->options['target_dir'].'/'.$filename, $content);

        return $filename;
    }

    private function generateUrlSafeRandomFileName()
    {
        $filename = 'fos_cache_litespeed_purger_';

        if (function_exists('random_bytes')) {
            $filename .= bin2hex(random_bytes(20));
        } else {
            $filename .= sha1(mt_rand().mt_rand().mt_rand());
        }

        $filename .= '.php';

        return $filename;
    }
}
