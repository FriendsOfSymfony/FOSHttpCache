<?php

namespace FOS\HttpCache\Test\PHPUnit;

class IsCacheMissConstraint extends AbstractCacheConstraint
{
    protected $value = 'MISS';

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'is a cache miss';
    }
}
