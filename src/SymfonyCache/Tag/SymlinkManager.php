<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tag manager which uses symlinks to reference actual cache entries.
 *
 * Each tag is represented by a physical folder, within which are symlinks
 * to the cache entries.
 *
 * When a tag is invalidated all of the symlink targets (the cache entries) are
 * removed, and then the tag directory is removed.
 */
class SymlinkManager implements ManagerInterface
{
    /**
     * @var Store
     */
    private $store;

    /**
     * @var string
     */
    private $filesystem;

    /**
     * @var string
     */
    private $baseTagPath;

    public function __construct(Store $store, $baseTagPath, Filesystem $filesystem = null)
    {
        $this->store = $store;
        $this->baseTagPath = $baseTagPath;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Return the concrete cache paths for the given tag.
     *
     * @param string $tag
     * @return string[]
     */
    public function getPathsForTag($tag)
    {
        $tagPath = $this->getTagPath($tag);

        if (!file_exists($tagPath)) {
            return array();
        }

        $filenames = scandir($tagPath);
        $paths = array();

        foreach ($filenames as $filename) {
            if (in_array($filename, array('.', '..'))) {
                continue;
            }

            $paths[] = realpath($tagPath . '/' . $filename);
        }

        return $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $purgeCount = 0;

        foreach ($tags as $tag) {
            $tagPath = $this->getTagPath($tag);
            $paths = $this->getPathsForTag($tag);

            // remove cache entries
            foreach ($paths as $path) {
                $this->filesystem->remove($path);
                $purgeCount++;
            }

            // remove the tag directory
            $this->filesystem->remove($tagPath);
        }

        return $purgeCount;
    }

    /**
     * {@inheritdoc}
     */
    public function tagCacheReference($tag, $cachereference) // hash, URL, ID
    {
        $tagPath = $this->getTagPath($tag);

        if (false === file_exists($tagPath)) {
            $this->filesystem->mkdir($tagPath);
        }

        $symlinkDest = $tagPath . '/' . $contentDigest;
        $symlinkOrig = $this->store->getPath($contentDigest);

        if (file_exists($symlinkDest)) {
            // if the tag already exists and refers to the same file, nothing to do.
            if (realpath($symlinkDest) === $symlinkOrig) {
                return $symlinkDest;
            }

            // otherwise, and this should not happen, just remove the link.
            $this->filesystem->remove($symlinkDest);
        }

        $this->filesystem->symlink($symlinkOrig, $symlinkDest);

        return $symlinkDest;
    }

    private function getTagPath($tag)
    {
        return $this->baseTagPath . '/' . $this->escapeTag($tag);
    }

    private function escapeTag($tag)
    {
        $tag = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tag);
        return $tag;
    }

}
