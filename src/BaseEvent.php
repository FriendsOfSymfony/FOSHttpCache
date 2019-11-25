<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache;

use Symfony\Component\EventDispatcher\Event as OldEvent;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(ContractEvent::class)) {
    class BaseEvent extends ContractEvent
    {
    }
} else {
    /**
     * @codeCoverageIgnore
     * @ignore This is purely for 3.4 comparability.
     */
    class BaseEvent extends OldEvent
    {
    }
}
