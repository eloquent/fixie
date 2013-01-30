<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Liberator\Liberator;
use Phake;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Yaml\Inline;

class FixtureWriterTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_handleClassName =
            __NAMESPACE__.'\CompactFixtureWriteHandle'
        ;
        $this->_renderer = new Inline;
        $this->_isolator = Phake::partialMock('Icecave\Isolator\Isolator');
        $this->_writer = new FixtureWriter(
            $this->_handleClassName,
            $this->_renderer,
            $this->_isolator
        );
        $this->_streams = array();
    }

    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->_streams as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    protected function streamFixture($data = '')
    {
        $this->streams[] = $stream = fopen(
            'data://text/plain;base64,'.base64_encode($data),
            'rb'
        );

        return $stream;
    }

    public function testConstructor()
    {
        $this->assertSame($this->_handleClassName, $this->_writer->handleClassName());
        $this->assertSame($this->_renderer, $this->_writer->renderer());
    }

    public function testConstructorDefaults()
    {
        $this->_writer = new FixtureWriter;

        $this->assertSame(
            __NAMESPACE__.'\SwitchingCompactFixtureWriteHandle',
            $this->_writer->handleClassName()
        );
        $this->assertInstanceOf(
            'Symfony\Component\Yaml\Inline',
            $this->_writer->renderer()
        );
    }

    public function testOpenFile()
    {
        $expected = new CompactFixtureWriteHandle(
            null,
            'foo',
            $this->_renderer,
            $this->_isolator
        );
        $actual = $this->_writer->openFile('foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->_renderer, $actual->renderer());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenFileCustomHandleClass()
    {
        $this->_writer = new FixtureWriter(
            __NAMESPACE__.'\ExpandedFixtureWriteHandle'
        );
        $expected = new ExpandedFixtureWriteHandle(
            null,
            'foo'
        );

        $this->assertEquals($expected, $this->_writer->openFile('foo'));
    }

    public function testOpenStream()
    {
        $stream = $this->streamFixture();
        $expected = new CompactFixtureWriteHandle(
            $stream,
            'foo',
            $this->_renderer,
            $this->_isolator
        );
        $actual = $this->_writer->openStream($stream, 'foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->_renderer, $actual->renderer());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenStreamCustomHandleClass()
    {
        $this->_writer = new FixtureWriter(
            __NAMESPACE__.'\ExpandedFixtureWriteHandle'
        );
        $stream = $this->streamFixture();
        $expected = new ExpandedFixtureWriteHandle(
            $stream,
            'foo'
        );

        $this->assertEquals($expected, $this->_writer->openStream($stream, 'foo'));
    }
}
