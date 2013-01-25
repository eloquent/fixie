<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader\Exception;

use Phake;
use PHPUnit_Framework_TestCase;

class EmptyHandleExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = Phake::mock('Exception');
        $exception = new EmptyHandleException(
            $previous
        );

        $this->assertSame("Stream and/or path must be provided.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
