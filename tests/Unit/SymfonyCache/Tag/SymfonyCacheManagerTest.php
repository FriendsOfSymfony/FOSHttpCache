<?php

namespace FOS\HttpCache\Tests\Unit\SymfonyCache\Tag;

use Symfony\Component\HttpFoundation\Response;
use FOS\HttpCache\Tag\StorageInterface;
use Symfony\Component\HttpKernel\HttpCache\Store;
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use FOS\HttpCache\SymfonyCache\Tag\SymfonyCacheManager;

class SymfonyCacheManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @var StorageInterface
     */
    private $tagStorage;

    /**
     * @var Store
     */
    private $cacheStorage;

    public function setUp()
    {
        $this->tagStorage = \Mockery::mock('FOS\HttpCache\Tag\StorageInterface');
        $this->cacheStorage = \Mockery::mock('Symfony\Component\HttpKernel\HttpCache\Store');
        $this->filesystem = \Mockery::mock('Symfony\Component\Filesystem\Filesystem');
        $this->manager = new SymfonyCacheManager($this->tagStorage, $this->cacheStorage);
    }

    /**
     * It should invalidate tags and remove linked cache entries
     */
    public function testInvalidateTags()
    {
        $tags = ['one', 'two'];
        $digests = ['1234', '5678'];

        $this->tagStorage->shouldReceive('getCacheIds')
            ->withArgs([$tags])
            ->andReturn($digests);

        foreach ($digests as $path => $digest) {
            $this->cacheStorage->shouldReceive('getPath')
                ->withArgs([$digest])
                ->andReturn($path);
            $this->filesystem->shouldReceive('remove')
                ->withArgs([$path]);
            $this->filesystem->shouldReceive('exists')
                ->withArgs([$path])
                ->andReturn(true);
        }

        $this->tagStorage->shouldReceive('removeTags')
            ->withArgs([$tags]);

        $this->manager->invalidateTags($tags);
    }

    /**
     * It should extract tags, content digest and expiry time from the HTTP
     * Response and associate the tags with the content digest in the store,
     * setting the expiry time based on the max age.
     */
    public function testTagResponse()
    {
        $tags = ['one', 'two'];
        $digest = 'abcd1234';
        $sMaxAge = 600;

        $this->tagStorage->shouldReceive('tagCacheId')
            ->withArgs([$tags, $digest, $sMaxAge]);
        $this->manager->tagCacheId($tags, $digest, $sMaxAge);
    }
}
