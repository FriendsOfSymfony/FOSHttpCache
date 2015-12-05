<?php

namespace FOS\HttpCache\Tests\Unit\SymfonyCache\Tag;

use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\Filesystem\Filesystem;
use FOS\HttpCache\SymfonyCache\Tag\SymlinkManager;


class SymlinkManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SymlinkManager
     */
    private $manager;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $http1Path;

    /**
     * @var string
     */
    private $http2Path;

    /**
     * @var string
     */
    private $http3Path;

    /**
     * @var string
     */
    private $tagsPath;

    /**
     * @var string
     */
    private $httpPath;

    public function setUp()
    {
        $this->cachePath = __DIR__ . '/cache';
        $this->tagsPath = $this->cachePath . '/tags';
        $this->httpPath = $this->cachePath . '/http';

        $this->filesystem = new Filesystem();
        $this->filesystem->remove($this->cachePath);
        $this->filesystem->mkdir($this->httpPath);

        $this->http1Path = $this->httpPath . '/cache1';
        $this->http2Path = $this->httpPath . '/cache2';
        $this->http3Path = $this->httpPath . '/cache3';

        foreach (array(
            $this->http1Path,
            $this->http2Path,
            $this->http3Path,
        ) as $i => $path) {
            file_put_contents(
                $path,
                '<html><h1>' . $i . ' Hello</h1></html>'
            );
        }

        $this->store = \Mockery::mock('Symfony\Component\HttpKernel\HttpCache\Store');
        $this->manager = new SymlinkManager($this->store, $this->tagsPath);
    }

    /**
     * It should create symlinks to cache entries
     * It should automatically create a non-existing cache directory
     * It should automatically create tag directories
     */
    public function testCreateTag()
    {
        $digest = '1234abcd';
        $tag = 'hello';
        $expectedLinkPath = $this->tagsPath . '/' . $tag . '/' . $digest;
        $this->store->shouldReceive('getPath')
            ->withArgs([$digest])
            ->andReturn($this->http1Path);

        $this->manager->tagDigest($tag, $digest);

        $this->assertFileExists($this->tagsPath . '/' . $tag);
        $this->assertFileExists($expectedLinkPath);
        $this->assertTrue(is_link($expectedLinkPath));
        $this->assertEquals(
            $this->http1Path,
            realpath($expectedLinkPath)
        );
    }

    /**
     * It should ignore existing references with the same destination
     */
    public function testReplaceReferenceSameDest()
    {
        $digest = '1234abcd';
        $this->store->shouldReceive('getPath')
            ->withArgs([$digest])
            ->andReturn($this->http1Path);

        $this->manager->tagDigest('hello', $digest);
        $this->assertFileExists($this->tagsPath . '/hello');

        $this->manager->tagDigest('hello', $digest);
        $this->assertFileExists($this->tagsPath . '/hello');
    }

    /**
     * It should replace existing references with different destination
     * (shouldn't happen normally)
     */
    public function testReplaceReferenceOtherDest()
    {
        $digest = '1234abcd';
        $expectedLinkPath = $this->tagsPath . '/hello/' . $digest;
        $this->store->shouldReceive('getPath')
            ->withArgs([$digest])
            ->andReturn($this->http1Path);
        $this->filesystem->mkdir($this->tagsPath . '/hello');
        $this->filesystem->symlink($this->http2Path, $this->tagsPath . '/hello/' . $digest);

        $this->manager->tagDigest('hello', $digest);
        $this->assertEquals(
            $this->http1Path,
            realpath($expectedLinkPath)
        );
    }

    /**
     * It should remove "tagged" cache entries.
     * It should remove the tag symlinks.
     * It should ignore non-existing tags.
     *
     * @depends testCreateTag
     */
    public function testInvalidate()
    {
        $digest1 = '1234abcd';
        $digest2 = 'abcd1234';
        $digest3 = 'a1b2c3d4';
        $this->store->shouldReceive('getPath')
            ->withArgs([$digest1])
            ->andReturn($this->http1Path);
        $this->store->shouldReceive('getPath')
            ->withArgs([$digest2])
            ->andReturn($this->http2Path);
        $this->store->shouldReceive('getPath')
            ->withArgs([$digest3])
            ->andReturn($this->http3Path);

        $sym1Path = $this->manager->tagDigest('one', $digest1);
        $sym2Path = $this->manager->tagDigest('two', $digest2);
        $sym3Path = $this->manager->tagDigest('one', $digest3);
        $real1Path = realpath($sym1Path);
        $real2Path = realpath($sym2Path);
        $real3Path = realpath($sym3Path);

        $this->assertFileExists($sym1Path);
        $this->assertFileExists($sym2Path);
        $this->assertFileExists($sym3Path);

        $this->manager->invalidateTags(['one']);
        $this->assertFileNotExists($sym1Path);
        $this->assertFileNotExists($real1Path);
        $this->assertFileExists($sym2Path);
        $this->assertFileExists($real2Path);
        $this->assertFileNotExists($sym3Path);
        $this->assertFileNotExists($real3Path);

        // invalidate multiple tags, do ignore non-existing tags
        $this->manager->invalidateTags(['one', 'two']);
        $this->assertFileNotExists($sym2Path);
        $this->assertFileNotExists($real2Path);
    }

    /**
     * It should invalidate tags with dead references.
     */
    public function testInvalidateTagDeadReferences()
    {
        $digest = '1234abcd';
        $this->store->shouldReceive('getPath')
            ->withArgs([$digest])
            ->andReturn($this->http1Path);

        $symlinkPath = $this->manager->tagDigest('one', $digest);

        // we have a dead link
        unlink($this->http1Path);
        $this->assertEquals(readlink($symlinkPath), $this->http1Path);
        $this->manager->invalidateTags(['one']);
    }
}
