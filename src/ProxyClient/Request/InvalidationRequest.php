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

use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestDecorator;
use Psr\Http\Message\RequestInterface;

/**
 * An invalidation instruction
 */
class InvalidationRequest implements RequestInterface
{
    use RequestDecorator;
    
    public function __construct($method, $uri, array $headers = [])
    {
        $this->message = MessageFactoryDiscovery::find()->createRequest(
            $method,
            $uri,
            '1.1',
            $headers
        );
    }
    
    /**
     * Get unique request signature
     *
     * This is used for removing duplicate requests from the queue.
     *
     * @return string
     */
    public function getSignature()
    {
        $headers = $this->getHeaders();
        ksort($headers);
        
        return md5($this->getMethod(). "\n" . $this->getUri(). "\n" . var_export($headers, true));
    }
}
