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

class IsCacheMissConstraint extends AbstractCacheConstraint
{
    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'is a cache miss';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return 'MISS';
    }
}
