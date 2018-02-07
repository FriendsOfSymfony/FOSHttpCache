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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Matches anonymous requests using a list of identification headers.
 */
class AnonymousRequestMatcher implements RequestMatcherInterface
{
    private $sessionNamePrefix;

    /**
     * @param string $sessionNamePrefix Prefix for session cookies. Must match your PHP session configuration
     */
    public function __construct($sessionNamePrefix)
    {
        $this->sessionNamePrefix = $sessionNamePrefix;
    }

    public function matches(Request $request)
    {
        // You might have to enable rewriting of the Authorization header in your server config or .htaccess:
        // RewriteEngine On
        // RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
        if ($request->server->has('AUTHORIZATION') ||
            $request->server->has('HTTP_AUTHORIZATION') ||
            $request->server->has('PHP_AUTH_USER')
        ) {
            return false;
        }

        foreach ($request->cookies as $name => $value) {
            if (0 === strpos($name, $this->sessionNamePrefix)) {
                return false;
            }
        }

        return true;
    }
}
