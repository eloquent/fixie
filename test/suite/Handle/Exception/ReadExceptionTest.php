<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Handle\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class ReadExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $cause = new Exception;
        $exception = new ReadException('foo', $cause);

        $this->assertSame("Unable to read data from file 'foo'.", $exception->getMessage());
        $this->assertSame('foo', $exception->path());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($cause, $exception->getPrevious());
    }

    public function testExceptionDefaults()
    {
        $exception = new ReadException;

        $this->assertSame('Unable to read data from stream.', $exception->getMessage());
        $this->assertNull($exception->path());
    }
}
