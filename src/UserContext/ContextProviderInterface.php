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
 * Allow a class to update a user context
 */
interface ContextProviderInterface
{
    /**
     * This function is called before generating the hash of a UserContext.
     *
     * This allow to add a parameter on UserContext or set the whole array of parameters
     *
     * @param UserContext $context
     */
    public function updateUserContext(UserContext $context);
}
