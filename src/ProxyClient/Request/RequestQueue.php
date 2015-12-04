<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient\Request;

use FOS\HttpCache\Exception\InvalidUrlException;
use Http\Discovery\UriFactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Stores each invalidation request and replicates it over all HTTP cache servers
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class RequestQueue implements \Countable
{
    /**
     * HTTP cache servers
     *
     * @var UriInterface[]
     */
    private $servers = [];

    /**
     * Application base URI
     *
     * @var UriInterface | null
     */
    private $baseUri;

    /**
     * @var InvalidationRequest[]
     */
    private $queue = [];

    /**
     * Constructor
     *
     * @param array $servers
     * @param null  $baseUri
     */
    public function __construct(
        array $servers,
        $baseUri = null
    ) {
        $this->setServers($servers);
        $this->setBaseUri($baseUri);
    }

    /**
     * Add invalidation request
     *
     * @param InvalidationRequest $request
     */
    public function add(InvalidationRequest $request)
    {
        $signature = $request->getSignature();

        if (!isset($this->queue[$signature])) {
            $this->queue[$signature] = $request;
        }
    }

    /**
     * Clear request queue
     */
    public function clear()
    {
        $this->queue = [];
    }

    public function count()
    {
        return count($this->queue);
    }

    /**
     * Get each invalidation request replicated over all HTTP caching servers
     *
     * @return RequestInterface[]
     */
    public function all()
    {
        $requests = [];
        foreach ($this->queue as $request) {
            $uri = $request->getUri();

            // If a base URI is configured, try to make partial invalidation
            // requests complete.
            if ($this->baseUri) {
                if ($uri->getHost()) {
                    // Absolute URI: does it already have a scheme?
                    if (!$uri->getScheme() && $this->baseUri->getScheme() !== '') {
                        $uri = $uri->withScheme($this->baseUri->getScheme());
                    }
                } else {
                    // Relative URI
                    if ($this->baseUri->getHost() !== '') {
                        $uri = $uri->withHost($this->baseUri->getHost());
                    }

                    if ($this->baseUri->getPort()) {
                        $uri = $uri->withPort($this->baseUri->getPort());
                    }

                    // Base path
                    if ($this->baseUri->getPath() !== '') {
                        $path = $this->baseUri->getPath() . '/' . ltrim($uri->getPath(), '/');
                        $uri = $uri->withPath($path);
                    }
                }
            }

            // Close connections to make sure invalidation (PURGE/BAN) requests
            // will not interfere with content (GET) requests.
            $request = $request->withUri($uri)->withHeader('Connection', 'Close');

            // Create a request to each caching proxy server
            foreach ($this->servers as $server) {
                $requests[] = $request->withUri(
                    $uri
                        ->withScheme($server->getScheme())
                        ->withHost($server->getHost())
                        ->withPort($server->getPort())
                    ,
                    true    // Preserve application Host header
                );
            }
        }

        return $requests;
    }

    /**
     * Set caching proxy servers
     *
     * @param array $servers Caching proxy proxy server hostnames or IP
     *                       addresses, including port if not port 80.
     *                       E.g. ['127.0.0.1:6081']
     *
     * @throws InvalidUrlException If server is invalid or contains URL
     *                             parts other than scheme, host, port
     */
    public function setServers(array $servers)
    {
        $this->servers = [];
        foreach ($servers as $server) {
            $this->servers[] = $this->filterUri($server, ['scheme', 'host', 'port']);
        }
    }

    /**
     * Set application base URI that will be prefixed to relative purge and
     * refresh requests
     *
     * @param string $uriString Your application’s base URI
     */
    private function setBaseUri($uriString = null)
    {
        if (null === $uriString) {
            $this->baseUri = null;

            return;
        }

        $this->baseUri = $this->filterUri($uriString);
    }

    /**
     * Filter a URL
     *
     * Prefix the URL with "http://" if it has no scheme, then check the URL
     * for validity. You can specify what parts of the URL are allowed.
     *
     * @param string       $uriString
     * @param string[]     $allowedParts Array of allowed URL parts (optional)
     *
     * @throws InvalidUrlException If URL is invalid, the scheme is not http or
     *                             contains parts that are not expected.
     *
     * @return UriInterface Filtered URI (with default scheme if there was no scheme)
     */
    private function filterUri($uriString, array $allowedParts = [])
    {
        // Creating a PSR-7 URI without scheme (with parse_url) results in the
        // original hostname to be seen as path. So first add a scheme if none
        // is given.
        if (false === strpos($uriString, '://')) {
            $uriString = sprintf('%s://%s', 'http', $uriString);
        }

        try {
            $uri = UriFactoryDiscovery::find()->createUri($uriString);
        } catch (\InvalidArgumentException $e) {
            throw InvalidUrlException::invalidUrl($uriString);
        }

        if (!$uri->getScheme()) {
            throw InvalidUrlException::invalidUrl($uriString, 'empty scheme');
        }

        if (count($allowedParts) > 0) {
            $parts = parse_url((string) $uri);
            $diff = array_diff(array_keys($parts), $allowedParts);
            if (count($diff) > 0) {
                throw InvalidUrlException::invalidUrlParts($uriString, $allowedParts);
            }
        }

        return $uri;
    }
}
