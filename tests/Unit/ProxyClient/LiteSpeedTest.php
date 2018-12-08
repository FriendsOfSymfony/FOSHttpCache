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
    private $documentRoot;

    protected function setUp()
    {
        $this->httpDispatcher = \Mockery::mock(HttpDispatcher::class);

        $deleteFilesAndFolders = function ($path) use (&$deleteFilesAndFolders) {
            $files = glob($path.'/*');
            foreach ($files as $file) {
                is_dir($file) ? $deleteFilesAndFolders($file) : unlink($file);
            }
            rmdir($path);

            return;
        };

        $documentRoot = sys_get_temp_dir().'/fos_ls_tests';
        if (is_dir($documentRoot)) {
            $deleteFilesAndFolders($documentRoot);
        }
        mkdir($documentRoot);

        $this->documentRoot = $documentRoot;
    }

    public function testPurge()
    {
        $ls = new LiteSpeed($this->httpDispatcher, [
            'document_root' => $this->documentRoot,
            'target_dir' => 'subfolder',
        ]);

        // We're also testing target_dir here so we have to create the subfolder
        mkdir($this->documentRoot.'/subfolder');

        $expectedContent = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: /url');
header('X-LiteSpeed-Purge: /another/url');
header('X-LiteSpeed-Purge: foo\'); exec(\'rm -rf /\');//');
header('X-LiteSpeed-Purge: foo\'); exec(\\\'rm -rf /\\\');//');

EOT;
        $this->assertLiteSpeedPurger([$expectedContent], 'subfolder');

        $ls->purge('/url');
        $ls->purge('/another/url');
        $ls->purge("foo'); exec('rm -rf /');//"); // Somebody tried something evil
        $ls->purge("foo'); exec(\'rm -rf /\');//"); // Somebody tried something even more evil
        $ls->flush();

        // Assert file has been deleted again
        $this->assertDirectoryEmpty($this->documentRoot.'/subfolder');
    }

    public function testPurgeWithAbsoluteUrls()
    {
        $ls = new LiteSpeed($this->httpDispatcher, [
            'document_root' => $this->documentRoot,
        ]);

        $expectedContents = [];
        $expectedContents[] = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: /url');

EOT;
        $expectedContents[] = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: /foobar');

EOT;
        $expectedContents[] = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: /foobar');

EOT;
        $this->assertLiteSpeedPurger($expectedContents);

        $ls->purge('/url');
        $ls->purge('https://www.domain.com/foobar');
        $ls->purge('https://www.domain.ch/foobar');
        $ls->flush();

        // Assert file has been deleted again
        $this->assertDirectoryEmpty($this->documentRoot);
    }

    public function testInvalidateTags()
    {
        $ls = new LiteSpeed($this->httpDispatcher, [
            'document_root' => $this->documentRoot,
        ]);

        $expectedContent = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: tag=foobar, tag=tag');
header('X-LiteSpeed-Purge: tag=more, tag=tags');

EOT;
        $this->assertLiteSpeedPurger([$expectedContent]);

        $ls->invalidateTags(['foobar', 'tag']);
        $ls->invalidateTags(['more', 'tags']);
        $ls->flush();

        // Assert file has been deleted again
        $this->assertDirectoryEmpty($this->documentRoot);
    }

    public function testClear()
    {
        $ls = new LiteSpeed($this->httpDispatcher, [
            'document_root' => $this->documentRoot,
        ]);

        $expectedContent = <<<'EOT'
<?php

header('X-LiteSpeed-Purge: *');

EOT;
        $this->assertLiteSpeedPurger([$expectedContent]);

        $ls->clear();
        $ls->flush();

        // Assert file has been deleted again
        $this->assertDirectoryEmpty($this->documentRoot);
    }

    private function assertLiteSpeedPurger(array $expectedContents, $targetDir = '')
    {
        $methodCallCount = 0;

        $this->httpDispatcher->shouldReceive('invalidate')
            ->times(count($expectedContents))
            ->with(\Mockery::on(
                function (RequestInterface $request) use ($expectedContents, $targetDir, &$methodCallCount) {
                    $this->assertEquals('GET', $request->getMethod());

                    $cutOff = $targetDir ? (strlen($targetDir) + 2) : 1;
                    $filename = substr_replace($request->getRequestTarget(), '', 0, $cutOff);

                    $path = $this->documentRoot;

                    if ($targetDir) {
                        $path .= '/'.$targetDir;
                    }

                    // Assert file has been generated
                    $this->assertFileExists($path.'/'.$filename);

                    // Assert file contents
                    $this->assertSame($expectedContents[$methodCallCount], file_get_contents($path.'/'.$filename));

                    ++$methodCallCount;

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
