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
 * A UserContext is a set of parameters which allow to determinate different views for an url
 *
 * For example a menu can be different if a user is authenticated or not,
 * in this case the UserContext should have a authenticated parameter set
 * to true if user is logged in or to false otherwise.
 */
class UserContext implements \IteratorAggregate
{
    private $parameters = array();

    /**
     * Set a parameter for this context
     *
     * @param string $key   Parameter identifier
     * @param mixed  $value Parameter value (it should be serializable)
     */
    public function addParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Set all the parameters of this context
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Determine whether a parameter exists
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasParameter($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Return all parameters of this context
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->parameters);
    }
}
