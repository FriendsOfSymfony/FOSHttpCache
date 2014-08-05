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

class IsCacheHitConstraint extends AbstractCacheConstraint
{
    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'is a cache hit';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return 'HIT';
    }
}
