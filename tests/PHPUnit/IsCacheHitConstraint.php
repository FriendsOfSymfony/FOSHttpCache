<?php

namespace FOS\HttpCache\Tests\PHPUnit;

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
