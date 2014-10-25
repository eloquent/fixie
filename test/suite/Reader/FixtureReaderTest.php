<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader;

use Eloquent\Liberator\Liberator;
use PHPUnit_Framework_TestCase;
use Phake;
use Symfony\Component\Yaml\Parser;

class FixtureReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new Parser();
        $this->isolator = Phake::partialMock('Icecave\Isolator\Isolator');
        $this->reader = new FixtureReader($this->parser, $this->isolator);

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
        $this->assertSame($this->parser, $this->reader->parser());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new FixtureReader();

        $this->assertInstanceOf('Symfony\Component\Yaml\Parser', $this->reader->parser());
    }

    public function testOpenFile()
    {
        $expected = new ReadHandle(null, 'foo', $this->parser, $this->isolator);
        $actual = $this->reader->openFile('foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->parser, $actual->parser());
        $this->assertSame($this->isolator, Liberator::liberate($actual)->isolator);
    }

    public function testOpenStream()
    {
        $stream = $this->streamFixture();
        $expected = new ReadHandle($stream, 'foo', $this->parser, $this->isolator);
        $actual = $this->reader->openStream($stream, 'foo');

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->parser, $actual->parser());
        $this->assertSame($this->isolator, Liberator::liberate($actual)->isolator);
    }
}
