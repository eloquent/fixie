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

use PHPUnit_Framework_TestCase;

class FixtureWriterTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_writer = new FixtureWriter;
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
        $this->assertSame(
            __NAMESPACE__.'\AlignedCompactFixtureWriteHandle',
            $this->_writer->handleClassName()
        );
    }

    public function testConstructorCustomHandleClass()
    {
        $this->_writer = new FixtureWriter('foo');

        $this->assertSame('foo', $this->_writer->handleClassName());
    }

    public function testOpenFile()
    {
        $expected = new AlignedCompactFixtureWriteHandle(
            null,
            'foo'
        );

        $this->assertEquals($expected, $this->_writer->openFile('foo'));
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
        $expected = new AlignedCompactFixtureWriteHandle(
            $stream,
            'foo'
        );

        $this->assertEquals($expected, $this->_writer->openStream($stream, 'foo'));
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
