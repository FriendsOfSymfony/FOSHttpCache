<?php

namespace FOS\HttpCache\Test\HttpClient;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Client\Tools\HttpAsyncClientEmulator;
use Http\Discovery\MessageFactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client mock
 *
 * This mock is most useful in tests. It does not send requests but stores them
 * for later retrieval. Additionally, you can set an exception to test
 * exception handling.
 */
class MockHttpClient implements HttpClient, HttpAsyncClient
{
    use HttpAsyncClientEmulator;

    private $requests = [];
    private $responses = [];
    private $exception;

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request, array $options = [])
    {
        $this->requests[] = $request;

        if ($this->exception) {
            throw $this->exception;
        }

        if (count($this->responses) > 0) {
            return array_shift($this->responses);
        }

        return MessageFactoryDiscovery::find()->createResponse();

    }

    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function addResponse(ResponseInterface $response)
    {
        $this->responses[] = $response;
    }

    public function getRequests()
    {
        return $this->requests;
    }

    public function clear()
    {
        $this->exception = null;
    }
}
