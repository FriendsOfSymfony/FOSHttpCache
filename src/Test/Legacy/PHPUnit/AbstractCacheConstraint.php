<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test\Legacy\PHPUnit;

use FOS\HttpCache\Test\PHPUnit\AbstractCacheConstraintTrait;

/**
 * Abstract cache constraint.
 */
abstract class AbstractCacheConstraint extends \PHPUnit_Framework_Constraint
{
    use AbstractCacheConstraintTrait;
}
