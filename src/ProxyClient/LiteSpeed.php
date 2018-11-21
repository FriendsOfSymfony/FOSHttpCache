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

        // Generate a reasonably random file name, no need to be cryptographically safe here
        $filename = 'fos_cache_litespeed_purger_'.substr(sha1(uniqid('', true).mt_rand()), 0, mt_rand(10, 40)) . '.php';

        file_put_contents($this->options['target_dir'].'/'.$filename, $content);

        return $filename;
    }
}
