<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Handler;

use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshInterface;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;

class RegexHandler extends CacheHandlerInterface
{
    /**
     * Constructor
     *
     * @param ProxyClientInterface $cache HTTP cache
     */
    public function __construct(BanInterface $cache)
    {
        parent::__construct($cache);
    }

    /**
     * Invalidate URLs based on a regular expression for the URI, an optional
     * content type and optional limit to certain hosts.
     *
     * The hosts parameter can either be a regular expression, e.g.
     * '^(www\.)?(this|that)\.com$' or an array of exact host names, e.g.
     * array('example.com', 'other.net'). If the parameter is empty, all hosts
     * are matched.
     *
     * Options:
     *
     *     content-type Regular expression pattern for the content
     *                  type to limit banning, for instance 'text'.
     *     hosts        Regular expression of a host name or list of
     *                  exact host names to limit banning.
     *
     * {@inheritDoc}
     */
    public function invalidate($regex, array $options = array())
    {
        $options = array_merge(array(
            'content-type' => null,
            'hosts' => null
        ), $options);

        $this->cache->banPath($regex, $options['content-type'], $options['hosts']);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh()
    {
    }
}
