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

use FOS\HttpCache\ProxyClient\HttpDispatcher;
use FOS\HttpCache\ProxyClient\LiteSpeed;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class LiteSpeedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HttpDispatcher|MockInterface
     */
    private $httpDispatcher;

    protected function setUp()
    {
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);
    }

    public function testPurge()
    {
        $ls = new LiteSpeed($this->httpDispatcher);

        $this->assertInvaliationRequests([
            ['X-LiteSpeed-Purge' => ['/url']],
            ['X-LiteSpeed-Purge' => ['/another/url']],
        ]);

        $ls->purge('/url');
        $ls->purge('/another/url');
        $ls->flush();
    }

    public function testPurgeWithAbsoluteUrls()
    {
        $ls = new LiteSpeed($this->httpDispatcher);

        $this->assertInvaliationRequests([
            ['X-LiteSpeed-Purge' => ['/url']],
            ['X-LiteSpeed-Purge' => ['/foobar']],
            ['X-LiteSpeed-Purge' => ['/foobar']],
        ]);

        $ls->purge('/url');
        $ls->purge('https://www.domain.com/foobar');
        $ls->purge('https://www.domain.ch/foobar');
        $ls->flush();
    }

    public function testInvalidateTags()
    {
        $ls = new LiteSpeed($this->httpDispatcher);

        $this->assertInvaliationRequests([
            ['X-LiteSpeed-Purge' => ['tag=foobar, tag=tag']],
            ['X-LiteSpeed-Purge' => ['tag=more, tag=tags']],
        ]);

        $ls->invalidateTags(['foobar', 'tag']);
        $ls->invalidateTags(['more', 'tags']);
        $ls->flush();
    }

    public function testClear()
    {
        $ls = new LiteSpeed($this->httpDispatcher);

        $this->assertInvaliationRequests([
            ['X-LiteSpeed-Purge' => ['*']],
        ]);

        $ls->clear();
        $ls->flush();
    }

    private function assertInvaliationRequests(array $expectedConsecutiveHeaders)
    {
        $methodCallCount = 0;

        $this->httpDispatcher->shouldReceive('invalidate')
            ->times(count($expectedConsecutiveHeaders))
            ->with(\Mockery::on(
                function (RequestInterface $request) use ($expectedConsecutiveHeaders, &$methodCallCount) {
                    $this->assertEquals('PURGE', $request->getMethod());
                    $this->assertSame($expectedConsecutiveHeaders[$methodCallCount], $request->getHeaders());

                    ++$methodCallCount;

                    return true;
                }
            ),
            true
            );

        $this->httpDispatcher->shouldReceive('flush')->once();
    }
}
