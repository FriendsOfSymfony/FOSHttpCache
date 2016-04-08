<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional\SymfonyHttpCache;

use FOS\HttpCache\ProxyClient\Symfony;
use FOS\HttpCache\Test\SymfonyTestCase;

/**
 * @group webserver
 * @group symfony
 */
class SymfonyHttpCacheTest extends SymfonyTestCase
{
    const TAGGED_RESPONSE_URL = '/symfony.php/tagged-response';

    /**
     * It should tag the response.
     */
    public function testTagResponse()
    {
        $headers = array(
            'tags' => json_encode(array('one', 'two', 'three'))
        );
        $this->assertMiss($this->getResponse(self::TAGGED_RESPONSE_URL, $headers));
        $this->assertHit($this->getResponse(self::TAGGED_RESPONSE_URL, $headers));
    }

    /**
     * It should invalidate certain tags.
     */
    public function testTagInvalidate()
    {
        // ensure the request is cached
        $this->testTagResponse();

        $response = $this->getResponse('/symfony.php/invalidate-tags', array(
            'tags' => json_encode(array('one'))
        ));

        $this->assertMiss($this->getResponse(self::TAGGED_RESPONSE_URL));
        $this->assertHit($this->getResponse(self::TAGGED_RESPONSE_URL));
    }
}

