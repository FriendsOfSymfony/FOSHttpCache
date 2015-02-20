<?php

namespace FOS\HttpCache\Test\HttpClient;

use Http\Adapter\Exception\MultiHttpAdapterException;
use Http\Adapter\HttpAdapter;
use GuzzleHttp\Psr7\Response;
use Http\Discovery\MessageFactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP adapter mock
 *
 * This mock is most useful in tests. It does not send requests but stores them
 * for later retrieval. Additionally, you can set an exception to test
 * exception handling.
 */
class MockHttpAdapter implements HttpAdapter
{
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

    /**
     * {@inheritdoc}
     */
    public function sendRequests(array $requests, array $options = [])
    {
        $responses = [];
        $exceptions = new MultiHttpAdapterException();

        foreach ($requests as $request) {
            try {
                $responses[] = $this->sendRequest($request);
            } catch (\Exception $e) {
                $exceptions->addException($e);
            }
        }

        if ($exceptions->hasExceptions()) {
            throw $exceptions;
        }

        return $responses;
    }

    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function addResponse(ResponseInterface $response)
    {
        $this->responses[] = $response;
    }

    /**
     * {@inheritdoc}
     *
     * @return string The name.
     */
    public function getName()
    {
        return 'mock';
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
