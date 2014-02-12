<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Liberator\Liberator;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;
use Symfony\Component\Yaml\Inline;

class FixtureWriterTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->handleClassName = 'Eloquent\Fixie\Writer\CompactFixtureWriteHandle';
        $this->renderer = new Inline;
        $this->isolator = Phake::partialMock(Isolator::className());
        $this->writer = new FixtureWriter($this->handleClassName, $this->renderer, $this->isolator);

        $this->streams = array();
    }

    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->streams as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    protected function streamFixture($data = '')
    {
        $this->streams[] = $stream = fopen('data://text/plain;base64,' . base64_encode($data), 'rb');

        return $stream;
    }

    public function testConstructor()
    {
        $this->assertSame($this->handleClassName, $this->writer->handleClassName());
        $this->assertSame($this->renderer, $this->writer->renderer());
    }

    public function testConstructorDefaults()
    {
        $this->writer = new FixtureWriter;

        $this->assertSame('Eloquent\Fixie\Writer\SwitchingCompactFixtureWriteHandle', $this->writer->handleClassName());
        $this->assertInstanceOf('Symfony\Component\Yaml\Inline', $this->writer->renderer());
    }

    public function testOpenFile()
    {
        $expected = new CompactFixtureWriteHandle(null, 'foo', $this->renderer, $this->isolator);
        $actual = $this->writer->openFile('foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->renderer, $actual->renderer());
        $this->assertSame($this->isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenFileCustomHandleClass()
    {
        $this->writer = new FixtureWriter('Eloquent\Fixie\Writer\ExpandedFixtureWriteHandle');
        $expected = new ExpandedFixtureWriteHandle(null, 'foo');

        $this->assertEquals($expected, $this->writer->openFile('foo'));
    }

    public function testOpenStream()
    {
        $stream = $this->streamFixture();
        $expected = new CompactFixtureWriteHandle($stream, 'foo', $this->renderer, $this->isolator);
        $actual = $this->writer->openStream($stream, 'foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->renderer, $actual->renderer());
        $this->assertSame($this->isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenStreamCustomHandleClass()
    {
        $this->writer = new FixtureWriter('Eloquent\Fixie\Writer\ExpandedFixtureWriteHandle');
        $stream = $this->streamFixture();
        $expected = new ExpandedFixtureWriteHandle($stream, 'foo');

        $this->assertEquals($expected, $this->writer->openStream($stream, 'foo'));
    }
}
