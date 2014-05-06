<?php

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
