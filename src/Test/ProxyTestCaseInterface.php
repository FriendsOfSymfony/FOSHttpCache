<?php

namespace FOS\HttpCache\Test;

use Guzzle\Http\Message\Response;

/**
 * HTTP caching proxy test case
 */
interface ProxyTestCaseInterface
{
    /**
     * Get HTTP test client that is configured for your caching proxy
     *
     * @return \Guzzle\Http\Client
     */
    public function getClient();

    /**
     * Get response through test client
     *
     * @param string $url
     * @param array  $headers HTTP headers (optional)
     * @param array  $options Request options (optional)
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function getResponse($url, array $headers = array(), $options = array());

    /**
     * Assert a HTTP cache hit
     *
     * @param Response $response Guzzle response
     * @param string   $message  Assertion failure message (optional)
     *
     * @throws \RuntimeException If cache header is not present
     * @throws \PHPUnit_Framework_ExpectationFailedException If assertions fails
     */
    public function assertHit(Response $response, $message = null);

    /**
     * Assert a HTTP cache hit
     *
     * @param Response $response Guzzle response
     * @param string   $message  Assertion failure message (optional)
     *
     * @throws \RuntimeException If cache header is not present
     * @throws \PHPUnit_Framework_ExpectationFailedException If assertions fails
     */
    public function assertMiss(Response $response, $message = null);
}
