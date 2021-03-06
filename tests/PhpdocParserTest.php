<?php

namespace Jasny\PhpdocParser\Tests;

use Jasny\PhpdocParser\PhpdocParser;
use Jasny\PhpdocParser\TagInterface;
use Jasny\PhpdocParser\TagSet;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\PhpdocParser\PhpdocParser
 */
class PhpdocParserTest extends TestCase
{
    /**
     * @var TagInterface[]|MockObject[]
     */
    protected $tags;

    /**
     * @var PhpdocParser
     */
    protected $parser;

    public function setUp()
    {
        $tags = [
            'foo' => $this->createConfiguredMock(TagInterface::class, ['getName' => 'foo']),
            'bar' => $this->createConfiguredMock(TagInterface::class, ['getName' => 'bar']),
            'qux' => $this->createConfiguredMock(TagInterface::class, ['getName' => 'qux']),
        ];

        $tagset = $this->createMock(TagSet::class);
        $tagset->expects($this->any())->method('offsetExists')->willReturnCallback(function($key) use ($tags) {
            return isset($tags[$key]);
        });
        $tagset->expects($this->any())->method('offsetGet')->willReturnCallback(function($key) use ($tags) {
            if (!isset($tags[$key])) {
                throw new \OutOfRangeException("Unknown tag '@{$key}'");
            }
            return $tags[$key];
        });

        $this->tags = $tags;
        $this->parser = new PhpdocParser($tagset);
    }

    public function testParseFlag()
    {
        $this->tags['foo']->expects($this->once())->method('process')
            ->with([], '')->willReturn(['foo' => true]);

        $doc = <<<DOC
/**
 * @foo
 */
DOC;

        $result = $this->parser->parse($doc);

        $this->assertEquals(['foo' => true], $result);
    }

    public function testParseFlagSeveral()
    {
        $this->tags['foo']->expects($this->once())->method('process')
            ->with([], '')->willReturn(['foo' => true]);

        $this->tags['bar']->expects($this->once())->method('process')
            ->with(['foo' => true], '')->willReturn(['foo' => true, 'bar' => true]);

        $doc = <<<DOC
/**
 * @foo
 * @bar
 */
DOC;

        $result = $this->parser->parse($doc);

        $this->assertEquals(['foo' => true, 'bar' => true], $result);
    }

    public function testParseValue()
    {
        $this->tags['foo']->expects($this->once())->method('process')
            ->with([], 'hello')->willReturn(['foo' => 'HELLO!']);

        $doc = <<<DOC
/**
 * @foo hello
 */
DOC;

        $result = $this->parser->parse($doc);

        $this->assertSame(['foo' => 'HELLO!'], $result);
    }

    public function testParseMultiple()
    {
        $this->tags['foo']->expects($this->exactly(3))->method('process')
            ->withConsecutive([[], ''], [['foo' => 1], ''], [['foo' => 2], ''])
            ->willReturnOnConsecutiveCalls(['foo' => 1], ['foo' => 2], ['foo' => 3]);

        $doc = <<<DOC
/**
 * @foo
 * @foo
 * @foo
 */
DOC;

        $result = $this->parser->parse($doc);

        $this->assertSame(['foo' => 3], $result);
    }

    public function testParseFull()
    {
        $this->tags['foo']->expects($this->exactly(2))->method('process')
            ->withConsecutive([[], 'hi'], [['foo' => ['hi'], 'bar' => true], 'bye'])
            ->willReturnOnConsecutiveCalls(['foo' => ['hi']], ['foo' => ['hi', 'bye'], 'bar' => true]);

        $this->tags['bar']->expects($this->once())->method('process')
            ->with(['foo' => ['hi']], '')->willReturn(['foo' => ['hi'], 'bar' => true]);

        $doc = <<<DOC
/**
 * This should be ignored, so should {@qux this}
 *
 * @foo hi
 * @bar
 * @foo bye
 * @ign
 */
DOC;

        $result = $this->parser->parse($doc);

        $this->assertEquals(['foo' => ['hi', 'bye'], 'bar' => true], $result);
    }
}
