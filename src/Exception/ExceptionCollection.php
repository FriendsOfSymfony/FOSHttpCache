<?php

namespace FOS\HttpCache\Exception;

/**
 * A collection of exceptions that might occur during the flush operation of a
 * CacheProxyInterface implementation
 */
class ExceptionCollection extends \Exception implements \IteratorAggregate, \Countable
{
    protected $exceptions = array();

    /**
     * Add an exception to the collection
     *
     * @param \Exception $e
     *
     * @return $this
     */
    public function add(\Exception $e)
    {
        if (null == $this->message) {
            $this->message = $e->getMessage();
        }

        $this->exceptions[] = $e;

        return $this;
    }

    /**
     * Get first exception in collection
     *
     * @return \Exception | null
     */
    public function getFirst()
    {
        if ($this->count() > 0) {
            return $this->exceptions[0];
        }
    }

    /**
     * Get exception iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->exceptions);
    }

    /**
     * Get number of exceptions in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->exceptions);
    }
}