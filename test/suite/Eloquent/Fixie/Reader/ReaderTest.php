<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader;

use Eloquent\Liberator\Liberator;
use Phake;
use PHPUnit_Framework_TestCase;

class ReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_isolator = Phake::partialMock('Icecave\Isolator\Isolator');
        $this->_reader = new Reader(
            $this->_isolator
        );

        $this->_compactData = <<<EOD
columns: [bar, baz]
data: [
[qux, doom],
[splat, ping],
]
EOD;
        $this->_expandedData = <<<EOD
- bar: qux
  baz: doom
- bar: splat
  baz: ping
EOD;
        $this->_compactStream = fopen(
            'data://text/plain;base64,'.base64_encode($this->_compactData),
            'rb'
        );
        $this->_expandedStream = fopen(
            'data://text/plain;base64,'.base64_encode($this->_expandedData),
            'rb'
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        if (is_resource($this->_compactStream)) {
            fclose($this->_compactStream);
        }
        if (is_resource($this->_expandedStream)) {
            fclose($this->_expandedStream);
        }
    }

    public function testOpenFileCompact()
    {
        Phake::when($this->_isolator)
            ->fopen(Phake::anyParameters())
            ->thenReturn($this->_compactStream)
        ;

        $actual = $this->_reader->openFile('foo');
        $expected = new CompactHandle(
            $this->_compactStream,
            'foo',
            null,
            $this->_isolator
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame('foo', $actual->path());
        $this->assertSame($this->_compactStream, $actual->stream());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenFileExpanded()
    {
        Phake::when($this->_isolator)
            ->fopen(Phake::anyParameters())
            ->thenReturn($this->_expandedStream)
        ;

        $actual = $this->_reader->openFile('foo');
        $expected = new ExpandedHandle(
            $this->_expandedStream,
            'foo',
            null,
            $this->_isolator
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame('foo', $actual->path());
        $this->assertSame($this->_expandedStream, $actual->stream());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenFileFailure()
    {
        Phake::when($this->_isolator)
            ->fopen(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        $this->_reader->openFile('foo');
    }

    public function testOpenStreamCompact()
    {
        $actual = $this->_reader->openStream($this->_compactStream, 'foo');
        $expected = new CompactHandle(
            $this->_compactStream,
            'foo',
            null,
            $this->_isolator
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame('foo', $actual->path());
        $this->assertSame($this->_compactStream, $actual->stream());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenStreamExpanded()
    {
        $actual = $this->_reader->openStream($this->_expandedStream, 'foo');
        $expected = new ExpandedHandle(
            $this->_expandedStream,
            'foo',
            null,
            $this->_isolator
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame('foo', $actual->path());
        $this->assertSame($this->_expandedStream, $actual->stream());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenStreamNoPath()
    {
        $actual = $this->_reader->openStream($this->_compactStream);
        $expected = new CompactHandle(
            $this->_compactStream,
            null,
            null,
            $this->_isolator
        );

        $this->assertEquals($expected, $actual);
        $this->assertNull($actual->path());
        $this->assertSame($this->_compactStream, $actual->stream());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenStreamFailureFgets()
    {
        Phake::when($this->_isolator)
            ->fgets(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        $this->_reader->openStream($this->_compactStream);
    }

    public function testOpenStreamFailureRewind()
    {
        Phake::when($this->_isolator)
            ->rewind(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        $this->_reader->openStream($this->_compactStream);
    }
}
