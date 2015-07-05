<?php

namespace FOS\HttpCache\ProxyClient\VarnishAdmin;

class Response
{
    private $statusCode;
    private $response;

    public function __construct($statusCode, $response)
    {
        $this->statusCode = (int) $statusCode;
        $this->response = $response;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
