<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Handler;

/**
 * Interface to be implemented by cache handlers
 *
 * Cache handlers are responsible for handling all aspects of specific types of
 * cache operations.
 *
 * They may implement any of the methods defined here. If the handler
 * does not require one of these methods then it should be implemented
 * as an empty method.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
interface CacheHandlerInterface
{
    /**
     * Invalidate the given subject
     *
     * @param mixed $subject
     * @param array $options
     */
    public function invalidate($subject, array $options = array());

    /**
     * Refresh the given subject
     *
     * The implementation can make this a noop
     *
     * @param mixed $subject
     * @param array $options
     */
    public function refresh($subject, array $options);

    /**
     * Flush the underlying cache implentation and return
     * the number of operations performed.
     *
     * @param mixed $subject
     * @param array $options
     *
     * @return integer
     */
    public function flush();

    /**
     * Return the class of the underlying cache implementation.
     *
     * This is used to keep track of which cache implementations
     * have been flushed.
     *
     * @return string
     */
    public function getCacheClass();
}
