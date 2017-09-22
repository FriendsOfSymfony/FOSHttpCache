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
     *
     * @var HttpDispatcher
     */
    private $httpDispatcher;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

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
     * @param HttpDispatcher      $httpDispatcher Helper to send HTTP requests to caching proxy
     * @param array               $options        Options for this client
     * @param RequestFactory|null $messageFactory Factory for PSR-7 messages. If none supplied,
     *                                            a default one is created
     */
    public function __construct(
        HttpDispatcher $httpDispatcher,
        array $options = [],
        RequestFactory $messageFactory = null
    ) {
        $this->httpDispatcher = $httpDispatcher;
        $this->options = $this->configureOptions()->resolve($options);
        $this->requestFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->httpDispatcher->flush();
    }

    /**
     * Get options resolver with default settings.
     *
     * @return OptionsResolver
     */
    protected function configureOptions()
    {
        return new OptionsResolver();
    }

    /**
     * Create a request and queue it with the HTTP dispatcher.
     *
     * @param string              $method
     * @param string|UriInterface $url
     * @param array               $headers
     * @param bool                $validateHost see HttpDispatcher::invalidate
     */
    protected function queueRequest($method, $url, array $headers, $validateHost = true)
    {
        $this->httpDispatcher->invalidate(
            $this->requestFactory->createRequest($method, $url, $headers),
            $validateHost
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

    /**
     * Splits a header value into an array of values. E.g. useful for tag
     * invalidation requests where you might need multiple requests if you
     * want to invalidate too many cache tags (so the header would get too long).
     *
     * @param string $value
     * @param int    $length
     * @param string $delimiter
     *
     * @return array
     */
    protected function splitLongHeaderValue($value, $length = 7500, $delimiter = ',')
    {
        if (mb_strlen($value) <= $length) {
            return [$value];
        }

        $tmp = [];
        $chunks = explode($delimiter, $value);
        $currentLength = 0;
        $index = 0;

        foreach ($chunks as $chunk) {
            $chunkLength = mb_strlen($chunk) + 1;

            if (($currentLength + $chunkLength) <= $length) {
                $tmp[$index][] = $chunk;
                $currentLength += $chunkLength;
            } else {
                ++$index;
                $currentLength = $chunkLength;
                $tmp[$index][] = $chunk;
            }
        }

        $result = [];
        foreach ($tmp as $values) {
            $result[] = implode($delimiter, $values);
        }

        return $result;
    }
}
