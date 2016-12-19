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
 * Generate a hash.
 */
interface HashGenerator
{
    /**
     * @return string The hash generated
     */
    public function generateHash();
}
