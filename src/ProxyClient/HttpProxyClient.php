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

use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestFactory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class for HTTP based caching proxy client.
 *
 * @author David de Boer <david@driebit.nl>
 */
abstract class HttpProxyClient implements ProxyClient
{
    /**
     * Dispatcher for invalidation HTTP requests.
     */
    private Dispatcher $httpDispatcher;

    private RequestFactory $requestFactory;

    /**
     * The options configured in the constructor argument or default values.
     *
     * @var array The resolved options
     */
    protected array $options;

    /**
     * The base class has no options.
     *
     * @param Dispatcher          $dispatcher     Helper to send instructions to the caching proxy
     * @param array               $options        Options for this client
     * @param RequestFactory|null $messageFactory Factory for PSR-7 messages. If none supplied,
     *                                            a default one is created
     */
    public function __construct(
        Dispatcher $dispatcher,
        array $options = [],
        ?RequestFactory $messageFactory = null
    ) {
        $this->httpDispatcher = $dispatcher;
        $this->options = $this->configureOptions()->resolve($options);
        $this->requestFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    public function flush(): int
    {
        return $this->httpDispatcher->flush();
    }

    /**
     * Get options resolver with default settings.
     */
    protected function configureOptions(): OptionsResolver
    {
        return new OptionsResolver();
    }

    /**
     * Create a request and queue it with the HTTP dispatcher.
     *
     * @param array<string, string>                $headers
     * @param bool                                 $validateHost see Dispatcher::invalidate
     * @param resource|string|StreamInterface|null $body
     */
    protected function queueRequest(string $method, UriInterface|string $url, array $headers, bool $validateHost = true, $body = null): void
    {
        $this->httpDispatcher->invalidate(
            $this->requestFactory->createRequest($method, $url, $headers, $body),
            $validateHost
        );
    }

    /**
     * Make sure that the tags are valid.
     *
     * Reusable function for proxy clients.
     * Escapes `,` and `\n` (newline) characters.
     *
     * Note: This is not a safe escaping function, it can lead to collisions,
     * e.g. between "foo,bar" and "foo_bar". But from the nature of the data,
     * such collisions are unlikely, and from the function of cache tagging,
     * collisions would in the worst case lead to unintended invalidations,
     * which is not a bug.
     *
     * @param string[] $tags The tags to escape
     *
     * @return string[] Sane tags
     */
    protected function escapeTags(array $tags): array
    {
        array_walk($tags, static function (&$tag) {
            // WARNING: changing the list of characters that are escaped is a BC break for existing installations,
            // as existing tags on the cache would not be invalidated anymore if they contain a character that is
            // newly escaped
            $tag = str_replace([',', "\n"], '_', $tag);
        });

        return $tags;
    }

    /**
     * Calculate how many tags fit into the header.
     *
     * This assumes that the tags are separated by one character.
     *
     * @param string[] $escapedTags
     * @param string   $glue        The concatenation string to use
     *
     * @return int Number of tags per tag invalidation request
     */
    protected function determineTagsPerHeader(array $escapedTags, string $glue): int
    {
        if (mb_strlen(implode($glue, $escapedTags)) < $this->options['header_length']) {
            return count($escapedTags);
        }

        /*
         * estimate the amount of tags to invalidate by dividing the max
         * header length by the largest tag (minus the glue length)
         */
        $tagsize = max(array_map('mb_strlen', $escapedTags));

        return floor($this->options['header_length'] / ($tagsize + strlen($glue))) ?: 1;
    }
}
