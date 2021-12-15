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
use Http\Message\RequestFactory;

/**
 * Cloudflare HTTP cache invalidator.
 *
 * Additional constructor options:
 * - zone_identifier       Identifier for your Cloudflare zone you want to purge the cache for
 * - authentication_token  API authorization token, requires Zone.Cache Purge permissions
 *
 * @author Simon Jones <simon@studio24.net>
 */
class Cloudflare extends HttpProxyClient implements ClearCapable, PurgeCapable, TagCapable
{
    /**
     * @see https://api.cloudflare.com/#getting-started-endpoints
     */
    private const API_ENDPOINT = '/client/v4';

    /**
     * Batch URL purge limit.
     *
     * @see https://api.cloudflare.com/#zone-purge-files-by-url
     */
    private const URL_BATCH_PURGE_LIMIT = 30;

    /**
     * Array of data to send to Cloudflare for purge by URLs request.
     *
     * To reduce the number of requests to cloudflare, we buffer the URLs.
     * During flush, we build requests with batches of URL_BATCH_PURGE_LIMIT.
     *
     * @var array<string|array{url: string, headers: string[]}>
     */
    private $purgeByUrlsData = [];

    public function __construct(
        Dispatcher $httpDispatcher,
        array $options = [],
        RequestFactory $messageFactory = null
    ) {
        if (!function_exists('json_encode')) {
            throw new \Exception('ext-json is required for cloudflare invalidation');
        }

        parent::__construct($httpDispatcher, $options, $messageFactory);
    }

    /**
     * {@inheritdoc}
     *
     * Tag invalidation only available with Cloudflare enterprise account
     *
     * @see https://api.cloudflare.com/#zone-purge-files-by-cache-tags,-host-or-prefix
     */
    public function invalidateTags(array $tags)
    {
        $this->queueRequest(
            'POST',
            sprintf(self::API_ENDPOINT.'/zones/%s/purge_cache', $this->options['zone_identifier']),
            [],
            false,
            $this->json_encode(['tags' => $tags])
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://api.cloudflare.com/#zone-purge-files-by-url
     * @see https://developers.cloudflare.com/cache/how-to/purge-cache#purge-by-single-file-by-url For details on headers you can pass to clear the cache correctly
     */
    public function purge($url, array $headers = [])
    {
        if (!empty($headers)) {
            $this->purgeByUrlsData[] = [
                'url' => $url,
                'headers' => $headers,
            ];
        } else {
            $this->purgeByUrlsData[] = $url;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://api.cloudflare.com/#zone-purge-all-files
     */
    public function clear()
    {
        $this->queueRequest(
            'POST',
            sprintf(self::API_ENDPOINT.'/zones/%s/purge_cache', $this->options['zone_identifier']),
            ['Accept' => 'application/json'],
            false,
            $this->json_encode(['purge_everything' => true])
        );

        return $this;
    }

    /**
     * {@inheritdoc} Queue requests for purge by URLs
     */
    public function flush()
    {
        // Queue requests for purge by URL
        foreach (\array_chunk($this->purgeByUrlsData, self::URL_BATCH_PURGE_LIMIT) as $urlChunk) {
            $this->queueRequest(
                'POST',
                sprintf(self::API_ENDPOINT.'/zones/%s/purge_cache', $this->options['zone_identifier']),
                [],
                false,
                $this->json_encode(['files' => $urlChunk])
            );
        }
        $this->purgeByUrlsData = [];

        return parent::flush();
    }

    /**
     * {@inheritdoc} Always provides authentication token
     */
    protected function queueRequest($method, $url, array $headers, $validateHost = true, $body = null)
    {
        parent::queueRequest(
            $method,
            $url,
            $headers + ['Authorization' => 'Bearer '.$this->options['authentication_token']],
            $validateHost,
            $body
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();

        $resolver->setRequired([
            'authentication_token',
            'zone_identifier',
        ]);

        return $resolver;
    }

    private function json_encode(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            throw new \InvalidArgumentException(sprintf('Cannot encode "$data": %s', json_last_error_msg()));
        }

        return $json;
    }
}
