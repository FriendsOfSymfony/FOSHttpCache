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

    /**
     * @var string
     */
    private $targetDir;

    protected function setUp()
    {
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);

        $targetDir = sys_get_temp_dir().'/fos_ls_tests';
        if (is_dir($targetDir)) {
            array_map('unlink', glob($targetDir.'/*'));
            rmdir($targetDir);
        }
        mkdir($targetDir);

        $this->targetDir = $targetDir;
    }

    public function testPurge()
    {
        $ls = new LiteSpeed($this->httpDispatcher, ['target_dir' => $this->targetDir]);

        $expectedContent = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: /url');
header('X-LiteSpeed-Purge: /another/url');

EOT;
        $this->assertLiteSpeedPurger($expectedContent);

        $ls->purge('/url');
        $ls->purge('/another/url');
        $ls->flush();

        // Assert file has been deleted again
        $this->assertDirectoryEmpty($this->targetDir);
    }

    public function testInvalidateTags()
    {
        $ls = new LiteSpeed($this->httpDispatcher, ['target_dir' => $this->targetDir]);

        $expectedContent = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: tag=foobar, tag=tag');
header('X-LiteSpeed-Purge: tag=more, tag=tags');

EOT;
        $this->assertLiteSpeedPurger($expectedContent);

        $ls->invalidateTags(['foobar', 'tag']);
        $ls->invalidateTags(['more', 'tags']);
        $ls->flush();

        // Assert file has been deleted again
        $this->assertDirectoryEmpty($this->targetDir);
    }

    public function testClear()
    {
        $ls = new LiteSpeed($this->httpDispatcher, ['target_dir' => $this->targetDir]);

        $expectedContent = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: *');

EOT;
        $this->assertLiteSpeedPurger($expectedContent);

        $ls->clear();
        $ls->flush();

        // Assert file has been deleted again
        $this->assertDirectoryEmpty($this->targetDir);
    }

    private function assertLiteSpeedPurger($expectedContent)
    {
        $this->httpDispatcher->shouldReceive('invalidate')->once()->with(
            \Mockery::on(
                function (RequestInterface $request) use ($expectedContent) {
                    $this->assertEquals('GET', $request->getMethod());

                    $filename = ltrim($request->getRequestTarget(), '/');

                    // Assert file has been generated
                    $this->assertFileExists($this->targetDir.'/'.$filename);

                    // Assert file contents
                    $this->assertSame($expectedContent, file_get_contents($this->targetDir.'/'.$filename));

                    return true;
                }
            ),
            true
        );
        $this->httpDispatcher->shouldReceive('flush')->once();
    }

    private function assertDirectoryEmpty($dir)
    {
        $isDirEmpty = !(new \FilesystemIterator($dir))->valid();

        $this->assertTrue($isDirEmpty, 'Directory is not empty');
    }
}
