<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\UserContext;

/**
 * Allow a class to update a user context.
 */
interface ContextProvider
{
    /**
     * This function is called before generating the hash of a UserContext.
     *
     * This allows to add parameters on UserContext or replace the whole array of parameters
     */
    public function updateUserContext(UserContext $context): void;
}
