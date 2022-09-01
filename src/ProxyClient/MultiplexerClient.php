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
use FOS\HttpCache\ProxyClient\Invalidation\ClearCapable;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\Invalidation\RefreshCapable;
use FOS\HttpCache\ProxyClient\Invalidation\TagCapable;

/**
 * This class forwards invalidation to all attached clients.
 *
 * @author Emanuele Panzeri <thepanz@gmail.com>
 */
class MultiplexerClient implements BanCapable, PurgeCapable, RefreshCapable, TagCapable, ClearCapable
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
                throw new InvalidArgumentException(
                    'Expected ProxyClientInterface, got: '.
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
     * @return int The number of cache invalidations performed per caching server
     *
     * @throws ExceptionCollection If any errors occurred during flush
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
     * Forwards tag invalidation request to all clients.
     *
     * {@inheritdoc}
     *
     * @return $this
     */
    public function invalidateTags(array $tags)
    {
        $this->invoke(TagCapable::class, 'invalidateTags', [$tags]);

        return $this;
    }

    /**
     * Forwards to all clients.
     *
     * @param string $url     Path or URL to purge
     * @param array  $headers Extra HTTP headers to send to the caching proxy (optional)
     *
     * @return $this
     */
    public function purge($url, array $headers = [])
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
     * Forwards to all clients.
     *
     * @return $this
     */
    public function clear()
    {
        $this->invoke(ClearCapable::class, 'clear', []);

        return $this;
    }

    /**
     * Invoke the given $method on all available ProxyClients implementing the
     * given $interface.
     *
     * @param string $interface The FQN of the interface
     * @param string $method    The method to invoke
     * @param array  $arguments The arguments to be passed to the method
     */
    private function invoke($interface, $method, array $arguments)
    {
        foreach ($this->getProxyClients($interface) as $proxyClient) {
            call_user_func_array([$proxyClient, $method], $arguments);
        }
    }

    /**
     * Get proxy clients that implement a feature interface.
     *
     * @param string $interface
     *
     * @return ProxyClient[]
     */
    private function getProxyClients($interface)
    {
        return array_filter(
            $this->proxyClients,
            function ($proxyClient) use ($interface) {
                return is_subclass_of($proxyClient, $interface);
            }
        );
    }
}
