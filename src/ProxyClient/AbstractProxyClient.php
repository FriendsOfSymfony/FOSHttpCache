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
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract HTTP based caching proxy client.
 *
 * @author David de Boer <david@driebit.nl>
 */
abstract class AbstractProxyClient implements ProxyClientInterface
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
     * @var array The resolved options.
     */
    protected $options;

    /**
     * Constructor
     *
     * Supported options:
     *
     * - base_uri Default application hostname, optionally including base URL,
     *   for purge and refresh requests (optional). This is required if you
     *   purge and refresh paths instead of absolute URLs.
     *
     * @param array                $servers        Caching proxy server hostnames or IP
     *                                             addresses, including port if not port 80.
     *                                             E.g. ['127.0.0.1:6081']
     * @param array                $options        List of options for the client.
     * @param HttpAsyncClient|null $httpClient     Client capable of sending HTTP requests. If no
     *                                             client is supplied, a default one is created.
     * @param MessageFactory|null  $messageFactory Factory for PSR-7 messages. If none supplied,
     *                                             a default one is created.
     */
    public function __construct(
        array $servers,
        array $options = [],
        HttpAsyncClient $httpClient = null,
        MessageFactory $messageFactory = null
    ) {
        if (!$httpClient) {
            $httpClient = HttpAsyncClientDiscovery::find();
        }
        $this->options = $this->getDefaultOptions()->resolve($options);
        $this->httpAdapter = new HttpAdapter($servers, $this->options['base_uri'], $httpClient);
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
        $resolver = new OptionsResolver();
        $resolver->setDefault('base_uri', null);

        return $resolver;
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
     * @param array $tags The tags to escape.
     *
     * @return array Sane tags.
     */
    protected function escapeTags(array $tags)
    {
        array_walk($tags, function (&$tag) {
            $tag = str_replace([',', "\n"], ['_', '_'], $tag);
        });

        return $tags;
    }
}
