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

/**
 * Abstract cache constraint
 */
abstract class AbstractCacheConstraint extends \PHPUnit_Framework_Constraint
{
    protected $header = 'X-Cache';

    /**
     * Constructor
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
     * Get cache header value
     *
     * @return string
     */
    abstract public function getValue();

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        if (!$other->hasHeader($this->header)) {
            throw new \RuntimeException(
                sprintf(
                    'Response has no "%s" header. Configure your caching proxy '
                    . 'to set the header with cache hit/miss status.',
                    $this->header
                )
            );
        }

        return $this->getValue() === (string) $other->getHeader($this->header);
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other)
    {
        return (string) $other . ' ' . $this->toString();
    }
}
