<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\UserContext;

use FOS\HttpCache\UserContext\AnonymousRequestMatcher;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AnonymousRequestMatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMatchAnonymousRequest()
    {
        $request = new Request();

        $requestMatcher = new AnonymousRequestMatcher([
            'user_identifier_headers' => ['Cookie', 'Authorization'],
            'session_name_prefix' => false,
        ]);

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testNoMatchIfCookie()
    {
        $request = new Request();
        $request->headers->set('Cookie', 'foo=bar');
        $request->cookies->set('foo', 'bar');

        $requestMatcher = new AnonymousRequestMatcher([
            'user_identifier_headers' => ['Cookie', 'Authorization'],
            'session_name_prefix' => false,
        ]);

        $this->assertFalse($requestMatcher->matches($request));
    }

    public function testNoMatchIfSession()
    {
        $request = new Request();
        $request->headers->set('Cookie', 'PHPSESSID7e476fc9f29f69d2ad6f11dbcd663b42=25f6d9c5a843e3c948cd26902385a527');
        $request->cookies->set('PHPSESSID7e476fc9f29f69d2ad6f11dbcd663b42', '25f6d9c5a843e3c948cd26902385a527');

        $requestMatcher = new AnonymousRequestMatcher([
            'user_identifier_headers' => ['Authorization'],
            'session_name_prefix' => 'PHPSESSID',
        ]);

        $this->assertFalse($requestMatcher->matches($request));
    }

    public function testMatchIfNoSessionCookie()
    {
        $request = new Request();
        $request->headers->set('Cookie', 'foo=bar');
        $request->cookies->set('foo', 'bar');

        $requestMatcher = new AnonymousRequestMatcher([
            'user_identifier_headers' => ['Authorization'],
            'session_name_prefix' => 'PHPSESSID',
        ]);

        $this->assertTrue($requestMatcher->matches($request));
    }

    public function testNoMatchIfAuthenticationHeader()
    {
        $request = new Request();
        $request->headers->set('Authorization', 'foo: bar');

        $requestMatcher = new AnonymousRequestMatcher([
            'user_identifier_headers' => ['Cookie', 'Authorization'],
            'session_name_prefix' => false,
        ]);

        $this->assertFalse($requestMatcher->matches($request));
    }
}
