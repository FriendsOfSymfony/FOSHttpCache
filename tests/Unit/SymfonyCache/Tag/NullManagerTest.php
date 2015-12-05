<?php

namespace FOS\HttpCache\Tests\Unit\SymfonyCache\Tag;

use FOS\HttpCache\SymfonyCache\Tag\NullManager;

class NullManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The null manager should do nothing successfully.
     */
    public function testNullManager()
    {
        $nullManager = new NullManager();
        $nullManager->tagDigest(array('one', 'two'), 'jasdmjag');
        $nullManager->invalidateTags(array('one', 'two'));
    }
}
