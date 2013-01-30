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
use Symfony\Component\Yaml\Parser;

class FixtureReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_parser = new Parser;
        $this->_isolator = Phake::partialMock('Icecave\Isolator\Isolator');
        $this->_reader = new FixtureReader(
            $this->_parser,
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
        $this->assertSame($this->_parser, $this->_reader->parser());
    }

    public function testConstructorDefaults()
    {
        $this->_reader = new FixtureReader;

        $this->assertInstanceOf(
            'Symfony\Component\Yaml\Parser',
            $this->_reader->parser()
        );
    }

    public function testOpenFile()
    {
        $expected = new ReadHandle(
            null,
            'foo',
            $this->_parser,
            $this->_isolator
        );
        $actual = $this->_reader->openFile('foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->_parser, $actual->parser());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenStream()
    {
        $stream = $this->streamFixture();
        $expected = new ReadHandle(
            $stream,
            'foo',
            $this->_parser,
            $this->_isolator
        );
        $actual = $this->_reader->openStream($stream, 'foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->_parser, $actual->parser());
        $this->assertSame($this->_isolator, Liberator::liberate($actual)->isolator);
    }
}
