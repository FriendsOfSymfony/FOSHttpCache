<?php

namespace FOS\HttpCache\Tests\PHPUnit;

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
