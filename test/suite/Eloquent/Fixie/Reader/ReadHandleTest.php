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

/**
 * @covers \Eloquent\Fixie\Reader\ReadHandle
 * @covers \Eloquent\Fixie\Handle\AbstractHandle
 */
class ReadHandleTest extends PHPUnit_Framework_TestCase
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
        $handle = new ReadHandle(
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
        $handle = new ReadHandle($stream);

        $this->assertNull($handle->path());
        $this->assertInstanceOf(
            'Symfony\Component\Yaml\Parser',
            $handle->parser()
        );
    }

    public function testConstructorFailureEmpty()
    {
        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\EmptyHandleException'
        );
        new ReadHandle;
    }

    public function testStreamFailureClosed()
    {
        $handle = new ReadHandle(
            null,
            'foo',
            null,
            $this->_isolator
        );
        $handle->close();

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ClosedHandleException'
        );
        $handle->stream();
    }

    public function testLazyStreamLoading()
    {
        $stream = $this->streamFixture();
        Phake::when($this->_isolator)
            ->fopen(Phake::anyParameters())
            ->thenReturn($stream)
        ;
        $handle = new ReadHandle(
            null,
            'foo',
            null,
            $this->_isolator
        );

        $this->assertSame($stream, $handle->stream());
        $this->assertSame($stream, $handle->stream());
        Phake::verify($this->_isolator)
            ->fopen('foo', 'rb')
        ;
    }

    public function testLazyStreamLoadingFailure()
    {
        $stream = $this->streamFixture();
        Phake::when($this->_isolator)
            ->fopen(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new ReadHandle(
            null,
            'foo',
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ReadException'
        );
        $handle->stream();
    }

    public function testClose()
    {
        $stream = $this->streamFixture();
        $handle = new ReadHandle(
            $stream,
            null,
            null,
            $this->_isolator
        );
        $handle->close();

        Phake::verify($this->_isolator)->fclose($this->identicalTo($stream));
    }

    public function testCloseNeverOpened()
    {
        $handle = new ReadHandle(
            null,
            'foo',
            null,
            $this->_isolator
        );
        $handle->close();

        Phake::verify($this->_isolator, Phake::never())
            ->fclose(Phake::anyParameters())
        ;
    }

    public function testCloseFailureAlreadyClosed()
    {
        $handle = new ReadHandle(
            null,
            'foo',
            null,
            $this->_isolator
        );
        $handle->close();

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ClosedHandleException'
        );
        $handle->close();
    }

    public function testCloseFailureFcloseError()
    {
        Phake::when($this->_isolator)
            ->fclose(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new ReadHandle(
            $this->streamFixture(),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ReadException'
        );
        $handle->close();
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
columns: [bar, baz]
data: [
[qux, doom],
]
EOD;
        $expected = array(
            array(
                'bar' => 'qux',
                'baz' => 'doom',
            ),
        );
        $data['Compact with column names, single row'] = array($expected, $yaml);

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

        $yaml = <<<'EOD'
- bar: qux
  baz: doom
- bar: splat
  baz: ping
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
        $data['Expanded'] = array($expected, $yaml);

        $yaml = <<<'EOD'
- bar: qux
  baz: doom
EOD;
        $expected = array(
            array(
                'bar' => 'qux',
                'baz' => 'doom',
            ),
        );
        $data['Expanded single row'] = array($expected, $yaml);

        $yaml = <<<'EOD'

- bar: qux

  baz: doom

- bar: splat

  baz: ping
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
        $data['Expanded interleaved whitespace'] = array($expected, $yaml);

        return $data;
    }

    /**
     * @dataProvider handleData
     */
    public function testHandle(array $expected, $yaml)
    {
        $handle = new ReadHandle(
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
        $stream = $this->streamFixture("\n\ncolumns: []\ndata: [\n]");
        $handle = new ReadHandle(
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
                22
            )
        );
    }

    public function testRewindOffsetCompactWithoutColumns()
    {
        $stream = $this->streamFixture("\n\ndata: [\n]");
        $handle = new ReadHandle(
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
                10
            )
        );
    }

    public function testRewindOffsetExpanded()
    {
        $stream = $this->streamFixture("\n\n- bar: qux\n  baz: doom\n- bar: splat\n  baz: ping");
        $handle = new ReadHandle(
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
                2
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
        $data['Compact wrong row data type'] = array($yaml);

        $yaml = <<<'EOD'
- ~
EOD;
        $data['Expanded wrong row data type'] = array($yaml);

        $yaml = <<<'EOD'
- bar: qux
  baz: doom
- bar: splat
  baz: ping
  pong: pang
EOD;
        $data['Expanded row key mismatch'] = array($yaml);

        return $data;
    }

    /**
     * @dataProvider handleFailureData
     */
    public function testHandleFailure($yaml)
    {
        $handle = new ReadHandle(
            $this->streamFixture($yaml),
            'foo',
            $this->_parser,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ReadException'
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
        $handle = new ReadHandle(
            $this->streamFixture(),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ReadException'
        );
        iterator_to_array($handle);
    }

    public function testFgetsFailure()
    {
        Phake::when($this->_isolator)
            ->fgets(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new ReadHandle(
            $this->streamFixture(),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ReadException'
        );
        iterator_to_array($handle);
    }

    public function testFtellFailure()
    {
        Phake::when($this->_isolator)
            ->ftell(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new ReadHandle(
            $this->streamFixture("data: [\n]"),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\ReadException'
        );
        iterator_to_array($handle);
    }
}
