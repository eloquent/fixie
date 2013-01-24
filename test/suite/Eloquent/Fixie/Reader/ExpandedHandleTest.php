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

/**
 * @covers \Eloquent\Fixie\Reader\AbstractHandle
 * @covers \Eloquent\Fixie\Reader\ExpandedHandle
 */
class ExpandedHandleTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_stream = $this->streamFixture('');
        $this->_parser = new Parser;
        $this->_isolator = Phake::partialMock('Icecave\Isolator\Isolator');
        $this->_handle = $this->handleFixture($this->_stream);
    }

    protected function tearDown()
    {
        parent::tearDown();

        if (is_resource($this->_stream)) {
            fclose($this->_stream);
        }
    }

    protected function streamFixture($data)
    {
        return fopen(
            'data://text/plain;base64,'.base64_encode($data),
            'rb'
        );
    }

    protected function handleFixture($stream)
    {
        return new ExpandedHandle(
            $stream,
            'foo',
            $this->_parser,
            $this->_isolator
        );
    }

    protected function handleFixtureByData($data)
    {
        return $this->handleFixture($this->streamFixture($data));
    }

    public function testConstructor()
    {
        $this->assertSame($this->_stream, $this->_handle->stream());
        $this->assertSame('foo', $this->_handle->path());
        $this->assertSame($this->_parser, $this->_handle->parser());
        $this->assertSame(0, $this->_handle->position());
    }

    public function testConstructorDefaults()
    {
        $this->_handle = new ExpandedHandle(
            $this->_stream
        );

        $this->assertNull($this->_handle->path());
        $this->assertInstanceOf(
            'Symfony\Component\Yaml\Parser',
            $this->_handle->parser()
        );
    }

    public function fetchData()
    {
        $data = array();

        $yaml = <<<EOD
EOD;
        $expected = array();
        $data['Empty file'] = array($expected, $yaml);

        $yaml = <<<EOD

EOD;
        $expected = array();
        $data['Empty file with trailing newline'] = array($expected, $yaml);

        $yaml = <<<EOD
- bar: qux
  baz: doom
- bar: splat
  baz: ping

EOD;
        $expected = array(
            array('bar' => 'qux', 'baz' => 'doom'),
            array('bar' => 'splat', 'baz' => 'ping'),
        );
        $data['With trailing newline'] = array($expected, $yaml);

        $yaml = <<<EOD
- bar: qux
  baz: doom
- bar: splat
  baz: ping
EOD;
        $expected = array(
            array('bar' => 'qux', 'baz' => 'doom'),
            array('bar' => 'splat', 'baz' => 'ping'),
        );
        $data['Without trailing newline'] = array($expected, $yaml);

        $yaml = <<<EOD
- bar: qux
- bar: splat
EOD;
        $expected = array(
            array('bar' => 'qux'),
            array('bar' => 'splat'),
        );
        $data['Single lines'] = array($expected, $yaml);

        return $data;
    }

    /**
     * @dataProvider fetchData
     */
    public function testFetch(array $expected, $data)
    {
        $this->_handle = $this->handleFixtureByData($data);

        foreach ($expected as $index => $expectedRow) {
            $this->assertSame($index, $this->_handle->position());
            $this->assertSame($expectedRow, $this->_handle->fetch());
        }
        $this->assertNull($this->_handle->fetch());
        $this->assertNull($this->_handle->position());
        $this->assertNull($this->_handle->fetch());
        $this->assertNull($this->_handle->position());
        $this->_handle->rewindHandle();
        foreach ($expected as $index => $expectedRow) {
            $this->assertSame($index, $this->_handle->position());
            $this->assertSame($expectedRow, $this->_handle->fetch());
        }
        $this->assertNull($this->_handle->fetch());
        $this->assertNull($this->_handle->position());
        $this->assertNull($this->_handle->fetch());
        $this->assertNull($this->_handle->position());
        $this->assertSame($expected, $this->_handle->fetchAll());
        $this->assertSame($expected, iterator_to_array($this->_handle));
    }

    public function fetchFailureData()
    {
        $data = array();

        $yaml = <<<EOD
?
EOD;
        $data['Invalid data'] = array($yaml);

        $yaml = '- ';
        $data['Empty row'] = array($yaml);

        $yaml = <<<EOD
- |
  foo
  bar
EOD;
        $data['Invalid row type'] = array($yaml);

        $yaml = <<<EOD
- bar: qux
  baz: doom
- bar: splat
  baz: ping
  pong: pang
EOD;
        $data['Column name mismatch'] = array($yaml);

        return $data;
    }

    /**
     * @dataProvider fetchFailureData
     */
    public function testFetchFailure($data)
    {
        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        $this->handleFixtureByData($data)->fetchAll();
    }

    public function testRewindStreamFailure()
    {
        Phake::when($this->_isolator)
            ->rewind(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        $this->handleFixtureByData('');
    }

    public function testReadLineFailure()
    {
        Phake::when($this->_isolator)
            ->fgets(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        $this->handleFixtureByData('');
    }

    public function testParseYamlFailure()
    {
        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        $this->handleFixtureByData("- foo: bar\n\tbaz: qux");
    }

    public function testParseYamlArrayFailure()
    {
        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        Liberator::liberate($this->_handle)->parseYamlArray('foo');
    }
}
