<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Exception;

/**
 * Wrapping the base exception for FOSHttpCache.
 */
class HandlerNotFoundException extends InvalidArgumentException
{
    public static function handlerNotFound($handler, $availableHanlders)
    {
        throw new HandlerNotFoundException(sprintf(
            'The handler "%s" has not been registered.'.
            'Registered handlers are: %s',
            $handler,
            implode(',', $availableHanlders)
        ));
    }
}
