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

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;

/**
 * This class forwards invalidation to all attached clients.
 *
 * @author Emanuele Panzeri <thepanz@gmail.com>
 */
class MultiplexerClient implements BanCapable, PurgeCapable, RefreshCapable
{
    /**
     * @var ProxyClient[]
     */
    private $proxyClients;

    /**
     * MultiplexerClient constructor.
     *
     * @param ProxyClient[] $proxyClients The list of Proxy clients
     */
    public function __construct(array $proxyClients)
    {
        foreach ($proxyClients as $proxyClient) {
            if (!$proxyClient instanceof ProxyClient) {
                throw new InvalidArgumentException('Expected ProxyClientInterface, got: '.
                    (is_object($proxyClient) ? get_class($proxyClient) : gettype($proxyClient))
                );
            }
        }

        $this->proxyClients = $proxyClients;
    }

    /**
     * Forwards to all clients.
     *
     * @param array $headers HTTP headers that path must match to be banned
     *
     * @return $this
     */
    public function ban(array $headers)
    {
        $this->invoke(BanCapable::class, 'ban', [$headers]);

        return $this;
    }

    /**
     * Forwards to all clients.
     *
     * @param string       $path        Regular expression pattern for URI to invalidate
     * @param string       $contentType Regular expression pattern for the content type to limit banning, for instance
     *                                  'text'
     * @param array|string $hosts       Regular expression of a host name or list of exact host names to limit banning
     *
     * @return $this
     */
    public function banPath($path, $contentType = null, $hosts = null)
    {
        $this->invoke(BanCapable::class, 'banPath', [$path, $contentType, $hosts]);

        return $this;
    }

    /**
     * Forwards to all clients.
     *
     * @throws ExceptionCollection If any errors occurred during flush
     *
     * @return int The number of cache invalidations performed per caching server
     */
    public function flush()
    {
        $count = 0;
        foreach ($this->proxyClients as $proxyClient) {
            $count += $proxyClient->flush();
        }

        return $count;
    }

    /**
     * Forwards to all clients.
     *
     * @param string $url     Path or URL to purge
     * @param array  $headers Extra HTTP headers to send to the caching proxy (optional)
     *
     * @return $this
     */
    public function purge($url, array $headers = array())
    {
        $this->invoke(PurgeCapable::class, 'purge', [$url, $headers]);

        return $this;
    }

    /**
     * Forwards to all clients.
     *
     * @param string $url     Path or URL to refresh
     * @param array  $headers Extra HTTP headers to send to the caching proxy (optional)
     *
     * @return $this
     */
    public function refresh($url, array $headers = [])
    {
        $this->invoke(RefreshCapable::class, 'refresh', [$url, $headers]);

        return $this;
    }

    /**
     * Helper function to invoke the given $method on all available ProxyClients implementing the given $interface.
     *
     * @param string $interface The FQN of the interface
     * @param string $method    The method to invoke
     * @param array  $arguments The arguments to be passed to the method
     */
    private function invoke($interface, $method, array $arguments)
    {
        foreach ($this->proxyClients as $proxyClient) {
            if (is_subclass_of($proxyClient, $interface)) {
                call_user_func_array([$proxyClient, $method], $arguments);
            }
        }
    }
}
