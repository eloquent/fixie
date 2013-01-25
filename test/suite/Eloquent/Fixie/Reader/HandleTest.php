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

class HandleTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_parser = Phake::partialMock('Symfony\Component\Yaml\Parser');
        $this->_isolator = Phake::partialMock('Icecave\Isolator\Isolator');
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
        $stream = $this->streamFixture();
        $handle = new Handle(
            $stream,
            'foo',
            $this->_parser,
            $this->_isolator
        );

        $this->assertSame($stream, $handle->stream());
        $this->assertSame('foo', $handle->path());
        $this->assertSame($this->_parser, $handle->parser());
    }

    public function testConstructorDefaults()
    {
        $this->_streams[] = $stream = fopen('data://text/plain;base64,', 'rb');
        $handle = new Handle($stream);

        $this->assertNull($handle->path());
        $this->assertInstanceOf(
            'Symfony\Component\Yaml\Parser',
            $handle->parser()
        );
    }

    public function handleData()
    {
        $data = array();

        $yaml = '';
        $expected = array();
        $data['Empty data'] = array($expected, $yaml);

        $yaml = <<<'EOD'
# comment
# comment
EOD;
        $expected = array();
        $data['Empty data except comments'] = array($expected, $yaml);

        $yaml = <<<'EOD'
columns: []
data: [
]
EOD;
        $expected = array();
        $data['Compact, empty, empty column names'] = array($expected, $yaml);

        $yaml = <<<'EOD'
columns: [bar, baz]
data: [
]
EOD;
        $expected = array();
        $data['Compact, empty, with column names'] = array($expected, $yaml);

        $yaml = <<<'EOD'
data: [
]
EOD;
        $expected = array();
        $data['Compact, empty, without column names'] = array($expected, $yaml);

        $yaml = <<<'EOD'
columns: [bar, baz]
data: [
[qux, doom],
[splat, ping],
]
EOD;
        $expected = array(
            array(
                'bar' => 'qux',
                'baz' => 'doom',
            ),
            array(
                'bar' => 'splat',
                'baz' => 'ping',
            ),
        );
        $data['Compact with column names'] = array($expected, $yaml);

        $yaml = <<<'EOD'
data: [
[qux, doom],
[splat, ping],
]
EOD;
        $expected = array(
            array(
                'qux',
                'doom',
            ),
            array(
                'splat',
                'ping',
            ),
        );
        $data['Compact without column names'] = array($expected, $yaml);

        $yaml = <<<'EOD'

columns: [bar, baz]

data: [

[qux, doom],

[splat, ping],

]
EOD;
        $expected = array(
            array(
                'bar' => 'qux',
                'baz' => 'doom',
            ),
            array(
                'bar' => 'splat',
                'baz' => 'ping',
            ),
        );
        $data['Interleaved whitespace'] = array($expected, $yaml);

        $yaml = <<<'EOD'
# comment
columns: [bar, baz]
# comment
data: [
# comment
[qux, doom],
# comment
[splat, ping],
# comment
]
# comment
EOD;
        $expected = array(
            array(
                'bar' => 'qux',
                'baz' => 'doom',
            ),
            array(
                'bar' => 'splat',
                'baz' => 'ping',
            ),
        );
        $data['Interleaved comments'] = array($expected, $yaml);

        $yaml = <<<'EOD'

columns: [  bar,   baz   ]
data: [
         [  qux,   doom  ],
         [  splat, ping  ],
]
EOD;
        $expected = array(
            array(
                'bar' => 'qux',
                'baz' => 'doom',
            ),
            array(
                'bar' => 'splat',
                'baz' => 'ping',
            ),
        );
        $data['Aligned data and columns'] = array($expected, $yaml);

        return $data;
    }

    /**
     * @dataProvider handleData
     */
    public function testHandle(array $expected, $yaml)
    {
        $handle = new Handle(
            $this->streamFixture($yaml),
            'foo',
            $this->_parser,
            $this->_isolator
        );

        $this->assertSame($expected, iterator_to_array($handle));
        $this->assertSame($expected, iterator_to_array($handle));
    }

    /**
     * @dataProvider handleData
     */
    public function testHandleTrailingNewline(array $expected, $yaml)
    {
        $this->testHandle($expected, $yaml."\n");
    }

    public function testRewindOffsetCompactWithColumns()
    {
        $stream = $this->streamFixture("columns: []\ndata: [\n]");
        $handle = new Handle(
            $stream,
            'foo',
            $this->_parser,
            $this->_isolator
        );
        iterator_to_array($handle);
        iterator_to_array($handle);

        Phake::inOrder(
            Phake::verify($this->_isolator)->fseek(
                $this->identicalTo($stream),
                0
            ),
            Phake::verify($this->_isolator)->fseek(
                $this->identicalTo($stream),
                20
            )
        );
    }

    public function testRewindOffsetCompactWithoutColumns()
    {
        $stream = $this->streamFixture("data: [\n]");
        $handle = new Handle(
            $stream,
            'foo',
            $this->_parser,
            $this->_isolator
        );
        iterator_to_array($handle);
        iterator_to_array($handle);

        Phake::inOrder(
            Phake::verify($this->_isolator)->fseek(
                $this->identicalTo($stream),
                0
            ),
            Phake::verify($this->_isolator)->fseek(
                $this->identicalTo($stream),
                8
            )
        );
    }

    public function handleFailureData()
    {
        $data = array();

        $yaml = "data: [\n\t[],\n]";
        $data['Invalid yaml data'] = array($yaml);

        $yaml = <<<'EOD'
~
EOD;
        $data['Invalid fixie data'] = array($yaml);

        $yaml = <<<'EOD'
columns: []
EOD;
        $data['Compact with columns but no data'] = array($yaml);

        $yaml = <<<'EOD'
data: [
EOD;
        $data['Compact with no columns and unclosed empty data'] = array($yaml);

        $yaml = <<<'EOD'
columns: [bar, baz]
data: [
[qux, doom],
EOD;
        $data['Compact with columns and unclosed partial data'] = array($yaml);

        $yaml = <<<'EOD'
columns: [bar, baz]
data: [
[qux, doom, splat],
]
EOD;
        $data['Compact with columns and row key mismatch'] = array($yaml);

        $yaml = <<<'EOD'
data: [
[qux, doom],
[splat, ping, pong],
]
EOD;
        $data['Compact without columns and row key mismatch'] = array($yaml);

        $yaml = <<<'EOD'
data: [
~,
]
EOD;
        $data['Wrong row data type'] = array($yaml);

        return $data;
    }

    /**
     * @dataProvider handleFailureData
     */
    public function testHandleFailure($yaml)
    {
        $handle = new Handle(
            $this->streamFixture($yaml),
            'foo',
            $this->_parser,
            $this->_isolator
        );

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        iterator_to_array($handle);
    }

    /**
     * @dataProvider handleFailureData
     */
    public function testHandleFailureTrailingNewline($yaml)
    {
        $this->testHandleFailure($yaml."\n");
    }

    public function testFseekFailure()
    {
        Phake::when($this->_isolator)
            ->fseek(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new Handle(
            $this->streamFixture(),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        iterator_to_array($handle);
    }

    public function testFgetsFailure()
    {
        Phake::when($this->_isolator)
            ->fgets(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new Handle(
            $this->streamFixture(),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        iterator_to_array($handle);
    }

    public function testFtellFailure()
    {
        Phake::when($this->_isolator)
            ->ftell(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new Handle(
            $this->streamFixture("data: [\n]"),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            __NAMESPACE__.'\Exception\ReadException'
        );
        iterator_to_array($handle);
    }
}