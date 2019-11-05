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

use Symfony\Component\EventDispatcher\Event as BaseEventDeprecated;
use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

// Symfony 4.3 BC layer
if (class_exists(BaseEvent::class)) {
    class MiddleManEvent extends BaseEvent
    {
    }
} else {
    class MiddleManEvent extends BaseEventDeprecated
    {
    }
}

class Event extends MiddleManEvent
{
    private $exception;

    /**
     * Set exception.
     *
     * @param \Exception $exception
     */
    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * Get exception.
     *
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
