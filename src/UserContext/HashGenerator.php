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

use FOS\HttpCache\Exception\InvalidArgumentException;

/**
 * Generate a hash for a UserContext by getting all the parameters needed across all registered services
 */
class HashGenerator
{
    /**
     * @var ContextProviderInterface[]
     */
    private $providers = array();

    /**
     * Constructor
     *
     * @param ContextProviderInterface[] $providers
     *
     * @throws InvalidArgumentException If no providers are supplied
     */
    public function __construct(array $providers)
    {
        if (0 === count($providers)) {
            throw new InvalidArgumentException('You must supply at least one provider');
        }

        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
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

    /**
     * Register a provider to be called for updating a UserContext before generating the Hash
     *
     * @param ContextProviderInterface $provider A context provider to be called to get context information about the current request.
     */
    private function registerProvider(ContextProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }
}
