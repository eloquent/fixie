<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Handle\Exception;

use Phake;
use PHPUnit_Framework_TestCase;

class WriteExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = Phake::mock('Exception');
        $exception = new WriteException(
            'foo',
            $previous
        );

        $this->assertSame("Unable to write data to file 'foo'.", $exception->getMessage());
        $this->assertSame('foo', $exception->path());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionDefaults()
    {
        $exception = new WriteException;

        $this->assertSame('Unable to write data to stream.', $exception->getMessage());
        $this->assertNull($exception->path());
    }
}
