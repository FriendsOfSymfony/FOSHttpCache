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
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use PHPUnit\Framework\TestCase;

class CommaSeparatedTagHeaderFormatterTest extends TestCase
{
    public function testGetDefaultTagsHeaderName(): void
    {
        $formatter = new CommaSeparatedTagHeaderFormatter();
        $this->assertSame('X-Cache-Tags', $formatter->getTagsHeaderName());
    }

    public function testGetCustomTagsHeaderName(): void
    {
        $formatter = new CommaSeparatedTagHeaderFormatter('Foobar');
        $this->assertSame('Foobar', $formatter->getTagsHeaderName());
    }

    public function testGetTagsHeaderValue(): void
    {
        $formatter = new CommaSeparatedTagHeaderFormatter();

        $this->assertSame('', $formatter->getTagsHeaderValue([]));
        $this->assertSame('tag1', $formatter->getTagsHeaderValue(['tag1']));
        $this->assertSame('tag1,tag2,tag3', $formatter->getTagsHeaderValue(['tag1', 'tag2', 'tag3']));
    }

    public function testGetCustomGlueTagsHeaderValue(): void
    {
        $formatter = new CommaSeparatedTagHeaderFormatter(TagHeaderFormatter::DEFAULT_HEADER_NAME, ' ');

        $this->assertSame('', $formatter->getTagsHeaderValue([]));
        $this->assertSame('tag1', $formatter->getTagsHeaderValue(['tag1']));
        $this->assertSame('tag1 tag2 tag3', $formatter->getTagsHeaderValue(['tag1', 'tag2', 'tag3']));
    }

    public function testParseTagsHeaderValue(): void
    {
        $parser = new CommaSeparatedTagHeaderFormatter();

        $this->assertSame(['a', 'b', 'c'], $parser->parseTagsHeaderValue('a,b,c'));
        $this->assertSame(['a', 'b', 'c'], $parser->parseTagsHeaderValue(['a', 'b,c']));
    }

    public function testParseCustomGlueTagsHeaderValue(): void
    {
        $parser = new CommaSeparatedTagHeaderFormatter(TagHeaderFormatter::DEFAULT_HEADER_NAME, ' ');

        $this->assertSame(['a', 'b,c'], $parser->parseTagsHeaderValue('a b,c'));
    }
}
