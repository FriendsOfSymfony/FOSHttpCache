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
use FOS\HttpCache\SymfonyCache\TagSubscriber;
use FOS\HttpCache\SymfonyCache\Tag\NullManager;
use FOS\HttpCache\SymfonyCache\Tag\ManagerInterface;

class AppCache extends HttpCache implements CacheInvalidationInterface
{
    use EventDispatchingHttpCache;

    public function __construct(HttpKernelInterface $kernel, StoreInterface $store, SurrogateInterface $surrogate = null, ManagerInterface $tagManager = null, array $options = array())
    {
        parent::__construct($kernel, $store, $surrogate, $options);

        $this->addSubscriber(new CustomTtlListener());
        $this->addSubscriber(new PurgeSubscriber(['purge_method' => 'NOTIFY']));
        $this->addSubscriber(new RefreshSubscriber());
        $this->addSubscriber(new UserContextSubscriber());

        $this->addSubscriber(new TagSubscriber($tagManager ?: new NullManager()));

        if (isset($options['debug']) && $options['debug']) {
            $this->addSubscriber(new DebugListener());
        }
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
