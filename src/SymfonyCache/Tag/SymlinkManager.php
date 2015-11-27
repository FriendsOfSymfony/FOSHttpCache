<?php

namespace FOS\HttpCache\SymfonyCache\Tag;

use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\Filesystem\Filesystem;

class SymlinkManager implements ManagerInterface
{
    private $store;
    private $filesystem;
    private $baseTagPath;

    public function __construct(Store $store, $baseTagPath)
    {
        $this->store = $store;
        $this->baseTagPath = $baseTagPath;
        $this->filesystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
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
    public function createTag($tag, $contentDigest)
    {
        $tagPath = $this->getTagPath($tag);

        if (false === file_exists($tagPath)) {
            $this->filesystem->mkdir($tagPath);
        }

        $symlinkDest = $tagPath . '/' . $contentDigest;
        $symlinkOrig = $this->store->getPath($contentDigest);

        if (file_exists($symlinkDest)) {
            $this->filesystem->remove($symlinkDest);
        }

        $this->filesystem->symlink($symlinkOrig, $symlinkDest);
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
