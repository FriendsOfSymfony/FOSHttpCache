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
 * A collection of exceptions that might occur during the flush operation of a
 * ProxyClientInterface implementation.
 */
class ExceptionCollection extends \Exception implements \IteratorAggregate, \Countable, HttpCacheException
{
    /**
     * @var \Throwable[]
     */
    private array $exceptions = [];

    /**
     * @param \Throwable[] $exceptions
     */
    public function __construct(array $exceptions = [])
    {
        foreach ($exceptions as $exception) {
            $this->add($exception);
        }
    }

    /**
     * Add an exception to the collection.
     */
    public function add(\Throwable $e): static
    {
        if (!$this->message) {
            $this->message = $e->getMessage();
        }

        $this->exceptions[] = $e;

        return $this;
    }

    /**
     * Get first exception in collection or null, if there is none.
     */
    public function getFirst(): ?\Throwable
    {
        if ($this->count() > 0) {
            return $this->exceptions[0];
        }

        return null;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->exceptions);
    }

    /**
     * Get number of exceptions in collection.
     */
    public function count(): int
    {
        return count($this->exceptions);
    }
}
