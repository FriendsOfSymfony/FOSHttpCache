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
    private array $proxyClients;

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
                    'Expected ProxyClientInterface, got: '.get_debug_type($proxyClient)
                );
            }
        }

        $this->proxyClients = $proxyClients;
    }

    public function ban(array $headers): static
    {
        $this->invoke(BanCapable::class, 'ban', [$headers]);

        return $this;
    }

    public function banPath(string $path, ?string $contentType = null, array|string|null $hosts = null): static
    {
        $this->invoke(BanCapable::class, 'banPath', [$path, $contentType, $hosts]);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Forwards to all clients.
     */
    public function flush(): int
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
     */
    public function invalidateTags(array $tags): static
    {
        if (!$tags) {
            return $this;
        }

        $this->invoke(TagCapable::class, 'invalidateTags', [$tags]);

        return $this;
    }

    /**
     * Forwards to all clients.
     *
     * {@inheritdoc}
     */
    public function purge(string $url, array $headers = []): static
    {
        $this->invoke(PurgeCapable::class, 'purge', [$url, $headers]);

        return $this;
    }

    /**
     * Forwards to all clients.
     *
     * {@inheritdoc}
     */
    public function refresh(string $url, array $headers = []): static
    {
        $this->invoke(RefreshCapable::class, 'refresh', [$url, $headers]);

        return $this;
    }

    /**
     * Forwards to all clients.
     *
     * {@inheritdoc}
     */
    public function clear(): static
    {
        $this->invoke(ClearCapable::class, 'clear', []);

        return $this;
    }

    /**
     * Invoke the given $method on all available ProxyClients implementing the
     * given $interface.
     *
     * @param string       $interface The FQN of the interface
     * @param string       $method    The method to invoke
     * @param array<mixed> $arguments The arguments to be passed to the method
     */
    private function invoke(string $interface, string $method, array $arguments): void
    {
        foreach ($this->getProxyClients($interface) as $proxyClient) {
            call_user_func_array([$proxyClient, $method], $arguments);
        }
    }

    /**
     * Get proxy clients that implement a feature interface.
     *
     * @param class-string $interface
     *
     * @return ProxyClient[]
     */
    private function getProxyClients(string $interface): array
    {
        return array_filter(
            $this->proxyClients,
            static function ($proxyClient) use ($interface) {
                return is_subclass_of($proxyClient, $interface);
            }
        );
    }
}
