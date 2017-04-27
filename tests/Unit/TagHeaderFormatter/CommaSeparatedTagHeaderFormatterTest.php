<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Unit\TagHeaderFormatter;

use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;

class CommaSeparatedTagHeaderFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTagsHeaderName()
    {
        $formatter = new CommaSeparatedTagHeaderFormatter('Foobar');
        $this->assertSame('Foobar', $formatter->getTagsHeaderName());
    }

    public function testGetTagsHeaderValue()
    {
        $formatter = new CommaSeparatedTagHeaderFormatter('Foobar');

        $this->assertSame('', $formatter->getTagsHeaderValue([]));
        $this->assertSame('tag1', $formatter->getTagsHeaderValue(['tag1']));
        $this->assertSame('tag1,tag2,tag3', $formatter->getTagsHeaderValue(['tag1', 'tag2', 'tag3']));
    }
}
