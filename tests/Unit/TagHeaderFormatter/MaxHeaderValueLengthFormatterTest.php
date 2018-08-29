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

use FOS\HttpCache\Exception\InvalidTagException;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use FOS\HttpCache\TagHeaderFormatter\MaxHeaderValueLengthFormatter;
use PHPUnit\Framework\TestCase;

class MaxHeaderValueLengthFormatterTest extends TestCase
{
    public function testNotTooLong()
    {
        $formatter = $this->getFormatter(50);
        $tags = ['foo', 'bar', 'baz'];

        $this->assertSame('foo,bar,baz', $formatter->getTagsHeaderValue($tags));
    }

    /**
     * @dataProvider tooLongProvider
     *
     * @param int   $maxLength
     * @param array $tags
     * @param mixed $expectedHeaderValue
     */
    public function testTooLong($maxLength, $tags, $expectedHeaderValue)
    {
        $formatter = $this->getFormatter($maxLength);
        $this->assertSame($expectedHeaderValue, $formatter->getTagsHeaderValue($tags));
    }

    public function testOneTagExceedsMaximum()
    {
        $this->expectException(InvalidTagException::class);
        $this->expectExceptionMessage('You configured a maximum header length of 3 but the tag "way-too-long-tag" is too long.');

        $formatter = $this->getFormatter(3);
        $formatter->getTagsHeaderValue(['way-too-long-tag']);
    }

    /**
     * @param int $maxLength
     *
     * @return MaxHeaderValueLengthFormatter
     */
    private function getFormatter($maxLength)
    {
        return new MaxHeaderValueLengthFormatter(
            new CommaSeparatedTagHeaderFormatter(),
            $maxLength
        );
    }

    public function tooLongProvider()
    {
        return [
            [3, ['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']],
            [6, ['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']], // with the , that equals 7
            [7, ['foo', 'bar', 'baz'], ['foo,bar', 'baz']],
            [50, ['foo', 'bar', 'baz'], 'foo,bar,baz'], // long enough, must not be an array
        ];
    }
}
