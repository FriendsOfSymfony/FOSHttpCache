<?php

namespace FOS\HttpCache\UserContext;

/**
 * Generate a hash for a UserContext by getting all the parameters needed across all registered services
 */
class HashGenerator
{
    private $providers = array();

    /**
     * Register a provider to be called for updating a UserContext before generating the Hash
     *
     * @param ContextProviderInterface $provider A context provider to be called to get context information about the current request.
     */
    public function registerProvider(ContextProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * Collect UserContext parameters and generate a hash from that
     *
     * @return string The hash generated
     */
    public function generateHash()
    {
        $userContext = new UserContext();

        foreach ($this->providers as $provider) {
            $provider->updateUserContext($userContext);
        }

        $parameters = $userContext->getParameters();

        // Sort by key (alphanumeric), as order should not make hash vary
        ksort($parameters);

        return hash("sha256", serialize($parameters));
    }
}
