<?php

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
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        return $this->value === (string) $other->getHeader($this->header);
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other)
    {
        return (string) $other . ' ' . $this->toString();
    }
}
