<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\Fixtures\Symfony;

use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppCache extends EventDispatchingHttpCache
{
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = parent::handle($request, $type, $catch);

        if ($response->headers->has('X-Symfony-Cache')) {
            if (false !== strpos($response->headers->get('X-Symfony-Cache'), 'miss')) {
                $state = 'MISS';
            } elseif (false !== strpos($response->headers->get('X-Symfony-Cache'), 'fresh')) {
                $state = 'HIT';
            } else {
                $state = 'UNDETERMINED';
            }
            $response->headers->set('X-Cache', $state);
        }

        return $response;
    }
}
