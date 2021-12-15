<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\ProxyClient;

use FOS\HttpCache\ProxyClient\Cloudflare;
use FOS\HttpCache\ProxyClient\HttpDispatcher;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class CloudflareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    const AUTH_TOKEN = 'abc123';
    const ZONE_IDENTIFIER = 'abcdef123abcdef123';

    /**
     * @var HttpDispatcher|MockInterface
     */
    private $httpDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);
    }

    protected function tearDown(): void
    {
        unset($this->httpDispatcher);
        parent::tearDown();
    }

    protected function getProxyClient(array $options = [])
    {
        $options = [
            'authentication_token' => self::AUTH_TOKEN,
            'zone_identifier' => self::ZONE_IDENTIFIER,
        ] + $options;

        return new Cloudflare($this->httpDispatcher, $options);
    }

    public function testInvalidateTagsPurge()
    {
        $cloudflare = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('POST', $request->getMethod());
                    $this->assertEquals('Bearer '.self::AUTH_TOKEN, current($request->getHeader('Authorization')));
                    $this->assertEquals(sprintf('/client/v4/zones/%s/purge_cache', self::ZONE_IDENTIFIER), $request->getRequestTarget());

                    $this->assertEquals('{"tags":["tag-one","tag-two"]}', $request->getBody()->getContents());

                    return true;
                }
            ),
            false
        );

        $cloudflare->invalidateTags(['tag-one', 'tag-two']);
    }

    public function testPurge()
    {
        $cloudflare = $this->getProxyClient();

        $expected = '{"files":["http://example.com/url-one","http://example.com/url-two",{"url":"http://example.com/url-three","headers":{"Origin":"https://www.cloudflare.com","CF-IPCountry":"US","CF-Device-Type":"desktop"}}]}';
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) use ($expected) {
                    $this->assertEquals('POST', $request->getMethod());
                    $this->assertEquals('Bearer '.self::AUTH_TOKEN, current($request->getHeader('Authorization')));
                    $this->assertEquals(sprintf('/client/v4/zones/%s/purge_cache', self::ZONE_IDENTIFIER), $request->getRequestTarget());

                    $this->assertEquals($expected, $request->getBody()->getContents());

                    return true;
                }
            ),
            false
        );
        $this->httpDispatcher->shouldReceive('flush')->once();

        $cloudflare->purge('http://example.com/url-one');
        $cloudflare->purge('http://example.com/url-two');
        $cloudflare->purge('http://example.com/url-three', [
            'Origin' => 'https://www.cloudflare.com',
            'CF-IPCountry' => 'US',
            'CF-Device-Type' => 'desktop'
        ]);
        $cloudflare->flush();
    }

    public function testClear()
    {
        $cloudflare = $this->getProxyClient();

        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) {
                    $this->assertEquals('POST', $request->getMethod());
                    $this->assertEquals('Bearer '.self::AUTH_TOKEN, current($request->getHeader('Authorization')));
                    $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
                    $this->assertEquals(sprintf('/client/v4/zones/%s/purge_cache', self::ZONE_IDENTIFIER), $request->getRequestTarget());

                    $this->assertEquals('{"purge_everything":true}', $request->getBody()->getContents());

                    return true;
                }
            ),
            false
        );

        $cloudflare->clear();
    }

}
