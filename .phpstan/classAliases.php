<?php

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\Compatibility\CacheInvalidationS6;
use FOS\HttpCache\SymfonyCache\Compatibility\CacheInvalidationLegacy;

/*
 * Hint phpstan on the class aliases. 
 * Symfony 6 introduced a BC break in the signature of the protected method HttpKernelInterface::fetch.
 * Load the correct interface to match the signature.
 */
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    class_alias(
        CacheInvalidationS6::class,
        CacheInvalidation::class
    );
} else {
    class_alias(
        CacheInvalidationLegacy::class,
        CacheInvalidation::class
    );
}