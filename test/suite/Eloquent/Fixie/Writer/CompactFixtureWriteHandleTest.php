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

use Phake;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Eloquent\Fixie\Writer\CompactFixtureWriteHandle
 * @covers \Eloquent\Fixie\Handle\AbstractHandle
 */
class CompactFixtureWriteHandleTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_generator = Phake::partialMock('Symfony\Component\Yaml\Inline');
        $this->_isolator = Phake::partialMock('Icecave\Isolator\Isolator');
        $this->_streams = array();
        $this->_output = '';

        $that = $this;
        Phake::when($this->_isolator)
            ->fwrite(Phake::anyParameters())
            ->thenGetReturnByLambda(function ($method, array $arguments) use ($that) {
                $that->_output .= $arguments[1];
            })
        ;
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

    protected function streamFixture()
    {
        $this->streams[] = $stream = fopen('php://temp', 'wb');

        return $stream;
    }

    public function testConstructor()
    {
        $stream = $this->streamFixture();
        $handle = new CompactFixtureWriteHandle(
            $stream,
            'foo',
            $this->_generator,
            $this->_isolator
        );

        $this->assertSame($stream, $handle->stream());
        $this->assertSame('foo', $handle->path());
        $this->assertSame($this->_generator, $handle->generator());
    }

    public function testConstructorDefaults()
    {
        $this->_streams[] = $stream = fopen('data://text/plain;base64,', 'wb');
        $handle = new CompactFixtureWriteHandle($stream);

        $this->assertNull($handle->path());
        $this->assertInstanceOf(
            'Symfony\Component\Yaml\Inline',
            $handle->generator()
        );
    }

    public function testConstructorFailureEmpty()
    {
        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\EmptyHandleException'
        );
        new CompactFixtureWriteHandle;
    }

    public function testStreamFailureClosed()
    {
        $handle = new CompactFixtureWriteHandle(
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
        $handle = new CompactFixtureWriteHandle(
            null,
            'foo',
            null,
            $this->_isolator
        );

        $this->assertSame($stream, $handle->stream());
        $this->assertSame($stream, $handle->stream());
        Phake::verify($this->_isolator)
            ->fopen('foo', 'wb')
        ;
    }

    public function testLazyStreamLoadingFailure()
    {
        $stream = $this->streamFixture();
        Phake::when($this->_isolator)
            ->fopen(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new CompactFixtureWriteHandle(
            null,
            'foo',
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\WriteException'
        );
        $handle->stream();
    }

    public function testClose()
    {
        $stream = $this->streamFixture();
        $handle = new CompactFixtureWriteHandle(
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
        $handle = new CompactFixtureWriteHandle(
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
        $handle = new CompactFixtureWriteHandle(
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
        $handle = new CompactFixtureWriteHandle(
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
columns: [bar, baz]
data: [
[qux, doom],
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
[qux, doom],
[splat, ping],
]

EOD;
        $data['Without column names'] = array($expected, $rows);

        return $data;
    }

    /**
     * @dataProvider writerData
     */
    public function testWriter($expected, array $rows)
    {
        $handle = new CompactFixtureWriteHandle(
            $this->streamFixture(),
            null,
            null,
            $this->_isolator
        );
        foreach ($rows as $row) {
            $handle->write($row);
        }
        $handle->close();

        $this->assertSame($expected, $this->_output);
    }

    public function testWriteDataFailure()
    {
        Phake::when($this->_isolator)
            ->fwrite(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;
        $handle = new CompactFixtureWriteHandle(
            $this->streamFixture(),
            null,
            null,
            $this->_isolator
        );

        $this->setExpectedException(
            'Eloquent\Fixie\Handle\Exception\WriteException'
        );
        $handle->write(array());
    }
}
