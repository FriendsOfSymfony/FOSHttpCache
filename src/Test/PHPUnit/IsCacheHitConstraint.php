<?php

namespace FOS\HttpCache\Test\PHPUnit;

class IsCacheHitConstraint extends AbstractCacheConstraint
{
    protected $value = 'HIT';

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'is a cache hit';
    }
}
