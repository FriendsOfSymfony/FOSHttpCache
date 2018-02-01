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

use PHPUnit\Framework\Constraint\Constraint;

if (class_exists('PHPUnit_Runner_Version') && version_compare(\PHPUnit_Runner_Version::id(), '6.0.0', '<')) {
    /*
     * Using an early return instead of a else does not work when using the PHPUnit phar due to some weird PHP behavior
     * (the class gets defined without executing the code before it and so the definition is not properly conditional)
     */
    class_alias('FOS\HttpCache\Test\Legacy\PHPUnit\AbstractCacheConstraint', 'FOS\HttpCache\Test\PHPUnit\AbstractCacheConstraint');
} else {
    /**
     * Abstract cache constraint.
     */
    abstract class AbstractCacheConstraint extends Constraint
    {
        use AbstractCacheConstraintTrait;
    }
}
