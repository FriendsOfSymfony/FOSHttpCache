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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Matches anonymous requests using a list of identification headers.
 */
class AnonymousRequestMatcher implements RequestMatcherInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @param array $options Configuration for the matcher. All options are required because this matcher is usually
     *                       created by the UserContextSubscriber which provides the default values.
     *
     * @throws \InvalidArgumentException if unknown keys are found in $options
     */
    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('user_identifier_headers', 'session_name_prefix'));

        $this->options = $resolver->resolve($options);
    }

    public function matches(Request $request)
    {
        // You might have to enable rewriting of the Authorization header in your server config or .htaccess:
        // RewriteEngine On
        // RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
        foreach ($this->options['user_identifier_headers'] as $header) {
            if ($request->headers->has($header)) {
                return false;
            }
        }

        foreach ($request->cookies as $name => $value) {
            if (0 === strpos($name, $this->options['session_name_prefix'])) {
                return false;
            }
        }

        return true;
    }
}
