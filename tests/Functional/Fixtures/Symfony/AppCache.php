<?php

namespace FOS\HttpCache\Tests\Functional\Fixtures\Symfony;

use FOS\HttpCache\SymfonyCache\CacheInvalidationInterface;
use FOS\HttpCache\SymfonyCache\CustomTtlListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use FOS\HttpCache\SymfonyCache\DebugListener;
use FOS\HttpCache\SymfonyCache\PurgeSubscriber;
use FOS\HttpCache\SymfonyCache\RefreshSubscriber;
use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\HttpCache\Store;
use FOS\HttpCache\Test\Proxy\SymfonyProxy;
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use Doctrine\Common\Cache\PhpFileCache;
use FOS\HttpCache\Tag\Storage\DoctrineCache;
use FOS\HttpCache\SymfonyCache\Tag\SymfonyCacheManager;

class AppCache extends HttpCache implements CacheInvalidationInterface
{
    use EventDispatchingHttpCache;

    public function __construct(
        HttpKernelInterface $kernel, 
        $cacheDir, 
        SurrogateInterface $surrogate = null, 
        array $options = array()
    )
    {
        // we need to instantiate the store early so we can share it.
        $store = new Store($cacheDir);

        // instantiate the tag storage and the Symfony HTTPCache tag manager.
        $tagStorage = new DoctrineCache(new PhpFileCache($cacheDir));
        $tagManager = new SymfonyCacheManager($tagStorage, $store);

        $this->addSubscriber(new CustomTtlListener());
        $this->addSubscriber(new PurgeSubscriber(['purge_method' => 'NOTIFY']));
        $this->addSubscriber(new RefreshSubscriber());
        $this->addSubscriber(new UserContextSubscriber());
        $this->addSubscriber(new TagSubscriber($tagManager));

        if (isset($options['debug']) && $options['debug']) {
            $this->addSubscriber(new DebugListener());
        }

        parent::__construct($kernel, $store, $surrogate, $options);
    }

    /**
     * Made public to allow event subscribers to do refresh operations.
     *
     * {@inheritDoc}
     */
    public function fetch(Request $request, $catch = false)
    {
        return parent::fetch($request, $catch);
    }
}
