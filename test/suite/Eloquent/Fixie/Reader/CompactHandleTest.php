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
 * @covers \Eloquent\Fixie\Reader\CompactHandle
 */
class CompactHandleTest extends PHPUnit_Framework_TestCase
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
        return new CompactHandle(
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
        $this->_handle = new CompactHandle(
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
columns: []
data: [
]
EOD;
        $expected = array();
        $data['Empty data'] = array($expected, $yaml);

        $yaml = <<<EOD
columns: []
data: [
]

EOD;
        $expected = array();
        $data['Empty data with trailing newline'] = array($expected, $yaml);

        $yaml = <<<EOD
columns: [bar, baz]
data: [
]
EOD;
        $expected = array();
        $data['Empty data with columns'] = array($expected, $yaml);

        $yaml = <<<EOD
data: [
]
EOD;
        $expected = array();
        $data['Empty data without columns'] = array($expected, $yaml);

        $yaml = <<<EOD
columns: [bar, baz]
data: [
[qux, doom],
[splat, ping],
]

EOD;
        $expected = array(
            array('bar' => 'qux', 'baz' => 'doom'),
            array('bar' => 'splat', 'baz' => 'ping'),
        );
        $data['With columns, with trailing newline'] = array($expected, $yaml);

        $yaml = <<<EOD
columns: [bar, baz]
data: [
[qux, doom],
[splat, ping],
]
EOD;
        $expected = array(
            array('bar' => 'qux', 'baz' => 'doom'),
            array('bar' => 'splat', 'baz' => 'ping'),
        );
        $data['With columns, without trailing newline'] = array($expected, $yaml);

        $yaml = <<<EOD
data: [
[qux, doom],
[splat, ping],
]

EOD;
        $expected = array(
            array('qux', 'doom'),
            array('splat', 'ping'),
        );
        $data['Without columns, with trailing newline'] = array($expected, $yaml);

        $yaml = <<<EOD
data: [
[qux, doom],
[splat, ping],
]
EOD;
        $expected = array(
            array('qux', 'doom'),
            array('splat', 'ping'),
        );
        $data['Without columns, without trailing newline'] = array($expected, $yaml);

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

        $yaml = <<<EOD
columns: [
EOD;
        $data['Columns not closed'] = array($yaml);

        $yaml = <<<EOD
columns: []
EOD;
        $data['Columns not followed by data'] = array($yaml);

        $yaml = <<<EOD
data: [

EOD;
        $data['Data only with no closure'] = array($yaml);

        $yaml = <<<EOD
columns: []
data: [

EOD;
        $data['Data with columns and no closure'] = array($yaml);

        $yaml = <<<EOD
columns: [bar]
data: [
[baz, qux],
]
EOD;
        $data['Key count mismatch with columns'] = array($yaml);

        $yaml = <<<EOD
data: [
[bar, baz, qux],
[doom, splat],
]
EOD;
        $data['Key count mismatch without columns'] = array($yaml);

        $yaml = <<<EOD
data: [
[bar, baz]
]
EOD;
        $data['Missing row separator'] = array($yaml);

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
