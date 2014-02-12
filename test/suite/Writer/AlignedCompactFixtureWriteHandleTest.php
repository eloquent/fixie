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

use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Eloquent\Fixie\Writer\AbstractWriteHandle
 * @covers \Eloquent\Fixie\Writer\AlignedCompactFixtureWriteHandle
 * @covers \Eloquent\Fixie\Handle\AbstractHandle
 */
class AlignedCompactFixtureWriteHandleTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->renderer = Phake::partialMock('Symfony\Component\Yaml\Inline');
        $this->isolator = Phake::partialMock(Isolator::className());
        $this->streams = array();
        $this->output = '';

        $that = $this;
        Phake::when($this->isolator)
            ->fwrite(Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($method, array $arguments) use ($that) {
                    $that->output .= $arguments[1];
                }
            );
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

    protected function streamFixture()
    {
        $this->streams[] = $stream = fopen('php://temp', 'wb');

        return $stream;
    }

    public function testConstructor()
    {
        $stream = $this->streamFixture();
        $handle = new AlignedCompactFixtureWriteHandle($stream, 'foo', $this->renderer, $this->isolator);

        $this->assertSame($stream, $handle->stream());
        $this->assertSame('foo', $handle->path());
        $this->assertSame($this->renderer, $handle->renderer());
    }

    public function testConstructorDefaults()
    {
        $this->streams[] = $stream = fopen('data://text/plain;base64,', 'wb');
        $handle = new AlignedCompactFixtureWriteHandle($stream);

        $this->assertNull($handle->path());
        $this->assertInstanceOf('Symfony\Component\Yaml\Inline', $handle->renderer());
    }

    public function testConstructorFailureEmpty()
    {
        $this->setExpectedException('Eloquent\Fixie\Handle\Exception\EmptyHandleException');
        new AlignedCompactFixtureWriteHandle;
    }

    public function testStreamFailureClosed()
    {
        $handle = new AlignedCompactFixtureWriteHandle(null, 'foo', null, $this->isolator);
        $handle->close();

        $this->setExpectedException('Eloquent\Fixie\Handle\Exception\ClosedHandleException');
        $handle->stream();
    }

    public function testLazyStreamLoading()
    {
        $stream = $this->streamFixture();
        Phake::when($this->isolator)->fopen(Phake::anyParameters())->thenReturn($stream);
        $handle = new AlignedCompactFixtureWriteHandle(null, 'foo', null, $this->isolator);

        $this->assertSame($stream, $handle->stream());
        $this->assertSame($stream, $handle->stream());
        Phake::verify($this->isolator)->fopen('foo', 'wb');
    }

    public function testLazyStreamLoadingFailure()
    {
        $stream = $this->streamFixture();
        Phake::when($this->isolator)->fopen(Phake::anyParameters())->thenThrow(Phake::mock('ErrorException'));
        $handle = new AlignedCompactFixtureWriteHandle(null, 'foo', null, $this->isolator);

        $this->setExpectedException('Eloquent\Fixie\Handle\Exception\WriteException');
        $handle->stream();
    }

    public function testClose()
    {
        $stream = $this->streamFixture();
        $handle = new AlignedCompactFixtureWriteHandle($stream, null, null, $this->isolator);
        $handle->close();

        Phake::verify($this->isolator)->fclose($this->identicalTo($stream));
    }

    public function testCloseNeverOpened()
    {
        $handle = new AlignedCompactFixtureWriteHandle(null, 'foo', null, $this->isolator);
        $handle->close();

        Phake::verify($this->isolator, Phake::never())->fclose(Phake::anyParameters());
    }

    public function testCloseFailureAlreadyClosed()
    {
        $handle = new AlignedCompactFixtureWriteHandle(null, 'foo', null, $this->isolator);
        $handle->close();

        $this->setExpectedException('Eloquent\Fixie\Handle\Exception\ClosedHandleException');
        $handle->close();
    }

    public function testCloseFailureFcloseError()
    {
        Phake::when($this->isolator)->fclose(Phake::anyParameters())->thenThrow(Phake::mock('ErrorException'));
        $handle = new AlignedCompactFixtureWriteHandle($this->streamFixture(), null, null, $this->isolator);

        $this->setExpectedException('Eloquent\Fixie\Handle\Exception\ReadException');
        $handle->close();
    }

    public function writerData()
    {
        $data = array();

        $rows = array();
        $expected = '';
        $data['Empty data'] = array($expected, $rows);

        $rows = array(
            array(
                'bar' => 'qux',
                'baz' => 'doom',
            ),
            array(
                'bar' => 'splat',
                'baz' => 'ping',
            ),
        );
        $expected = <<<'EOD'
columns:
 [bar,   baz ]
data: [
 [qux,   doom],
 [splat, ping],
]

EOD;
        $data['With column names'] = array($expected, $rows);

        $rows = array(
            array(
                'qux',
                'doom',
            ),
            array(
                'splat',
                'ping',
            ),
        );
        $expected = <<<'EOD'
data: [
[qux,   doom],
[splat, ping],
]

EOD;
        $data['Without column names'] = array($expected, $rows);

        $rows = array(
            array(
                'bar' => 'qux',
                'bazbaz' => 'doom',
            ),
            array(
                'bar' => 'splat',
                'bazbaz' => 'ping',
            ),
        );
        $expected = <<<'EOD'
columns:
 [bar,   bazbaz]
data: [
 [qux,   doom  ],
 [splat, ping  ],
]

EOD;
        $data['With longer column name'] = array($expected, $rows);

        return $data;
    }

    /**
     * @dataProvider writerData
     */
    public function testWriter($expected, array $rows)
    {
        $handle = new AlignedCompactFixtureWriteHandle($this->streamFixture(), null, null, $this->isolator);
        foreach ($rows as $row) {
            $handle->write($row);
        }
        $handle->close();

        $this->assertSame($expected, $this->output);
    }

    /**
     * @dataProvider writerData
     */
    public function testWriterWriteAll($expected, array $rows)
    {
        $handle = new AlignedCompactFixtureWriteHandle($this->streamFixture(), null, null, $this->isolator);
        $handle->writeAll($rows);
        $handle->close();

        $this->assertSame($expected, $this->output);
    }

    public function testWriteFailureColumnNameMismatch()
    {
        $handle = new AlignedCompactFixtureWriteHandle($this->streamFixture(), null, null, $this->isolator);
        $handle->write(array('foo' => 'bar'));

        $this->setExpectedException('Eloquent\Fixie\Handle\Exception\WriteException');
        $handle->write(array('baz' => 'qux'));
    }

    public function testWriteDataFailure()
    {
        Phake::when($this->isolator)->fwrite(Phake::anyParameters())->thenThrow(Phake::mock('ErrorException'));
        $handle = new AlignedCompactFixtureWriteHandle($this->streamFixture(), null, null, $this->isolator);
        $handle->write(array());

        $this->setExpectedException('Eloquent\Fixie\Handle\Exception\WriteException');
        $handle->close();
    }
}
