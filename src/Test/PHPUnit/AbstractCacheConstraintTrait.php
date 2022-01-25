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

use PHPUnit\Runner\Version;
use Psr\Http\Message\ResponseInterface;

/**
 * This trait is used to have the same code and behavior between AbstractCacheConstraint and its legacy version.
 */
trait AbstractCacheConstraintTrait
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

        if (version_compare(Version::id(), '8.0.0', '<')) {
            parent::__construct();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param ResponseInterface $other The guzzle response object
     */
    public function matches($other): bool
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

            $message .= "\nThe response headers are:\n\n";

            foreach ($other->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    $message .= $name.': '.$value."\n";
                }
            }

            $body = $other->getBody();
            $body->rewind();
            $message .= sprintf("\nThe response body is:\n\n %s", $body->getContents());

            throw new \RuntimeException($message);
        }

        return false !== strpos((string) $other->getHeaderLine($this->header), $this->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function failureDescription($other): string
    {
        return sprintf(
            'response (with status code %s) %s',
            $other->getStatusCode(),
            $this->toString()
        );
    }
}
