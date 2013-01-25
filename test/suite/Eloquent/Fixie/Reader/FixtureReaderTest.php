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

use Phake;
use PHPUnit_Framework_TestCase;

class FixtureReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_reader = new FixtureReader;
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

    public function testReadFile()
    {
        $expected = new Handle(
            null,
            'foo'
        );

        $this->assertEquals($expected, $this->_reader->readFile('foo'));
    }

    public function testReadStream()
    {
        $stream = $this->streamFixture();
        $expected = new Handle(
            $stream,
            'foo'
        );

        $this->assertEquals($expected, $this->_reader->readStream($stream, 'foo'));
    }
}
