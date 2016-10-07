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

use FOS\HttpCache\ProxyClient\Http\HttpAdapter;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class for HTTP based caching proxy client.
 *
 * @author David de Boer <david@driebit.nl>
 */
abstract class HttpProxyClient implements ProxyClientInterface
{
    /**
     * HTTP client adapter.
     *
     * @var HttpAdapter
     */
    protected $httpAdapter;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * The options configured in the constructor argument or default values.
     *
     * @var array The resolved options
     */
    protected $options;

    /**
     * Constructor.
     *
     * The base class has no options.
     *
     * @param HttpAdapter         $httpAdapter    Helper to send HTTP requests to caching proxy
     * @param array               $options        Options for this client
     * @param MessageFactory|null $messageFactory Factory for PSR-7 messages. If none supplied,
     *                                            a default one is created
     */
    public function __construct(
        HttpAdapter $httpAdapter,
        array $options = [],
        MessageFactory $messageFactory = null
    ) {
        $this->httpAdapter = $httpAdapter;
        $this->options = $this->getDefaultOptions()->resolve($options);
        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->httpAdapter->flush();
    }

    /**
     * Get options resolver with default settings.
     *
     * @return OptionsResolver
     */
    protected function getDefaultOptions()
    {
        return new OptionsResolver();
    }

    /**
     * Create a request and queue it with the HttpAdapter.
     *
     * @param string              $method
     * @param string|UriInterface $url
     * @param array               $headers
     */
    protected function queueRequest($method, $url, array $headers)
    {
        $this->httpAdapter->invalidate(
            $this->messageFactory->createRequest($method, $url, $headers)
        );
    }

    /**
     * Make sure that the tags are valid.
     *
     * Reusable function for proxy clients.
     * Escapes `,` and `\n` (newline) characters.
     *
     * @param array $tags The tags to escape
     *
     * @return array Sane tags
     */
    protected function escapeTags(array $tags)
    {
        array_walk($tags, function (&$tag) {
            $tag = str_replace([',', "\n"], ['_', '_'], $tag);
        });

        return $tags;
    }
}
