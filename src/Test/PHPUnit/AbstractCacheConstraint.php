<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test\PHPUnit;

use Psr\Http\Message\ResponseInterface;

/**
 * Abstract cache constraint.
 */
abstract class AbstractCacheConstraint extends \PHPUnit_Framework_Constraint
{
    protected $header = 'X-Cache';

    /**
     * Constructor.
     *
     * @param string $header Cache debug header; defaults to X-Cache-Debug
     */
    public function __construct($header = null)
    {
        if ($header) {
            $this->header = $header;
        }

        parent::__construct();
    }

    /**
     * Get cache header value.
     *
     * @return string
     */
    abstract public function getValue();

    /**
     * {@inheritdoc}
     *
     * @param Response $other The guzzle response object
     */
    protected function matches($other)
    {
        if (!$other instanceof ResponseInterface) {
            throw new \RuntimeException(sprintf('Expected a GuzzleHttp\Psr7\Response but got %s', get_class($other)));
        }
        if (!$other->hasHeader($this->header)) {
            $message = sprintf(
                'Response has no "%s" header. Configure your caching proxy '
                .'to set the header with cache hit/miss status.',
                $this->header
            );
            if (200 !== $other->getStatusCode()) {
                $message .= sprintf("\nStatus code of response is %s.", $other->getStatusCode());
            }

            throw new \RuntimeException($message);
        }

        return strpos((string) $other->getHeaderLine($this->header), $this->getValue()) !== false;
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other)
    {
        return sprintf(
            'response (with status code %s) %s',
            $other->getStatusCode(),
            $this->toString()
        );
    }
}
