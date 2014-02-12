<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader;

use Eloquent\Fixie\Handle\Exception\ReadException;
use Eloquent\Fixie\Handle\HandleInterface;
use Iterator;

/**
 * The interface implemented by readable data handles.
 */
interface ReadHandleInterface extends HandleInterface, Iterator
{
    /**
     * Read and return a single data row.
     *
     * @return array|null    The data row, or null if the end of data was encountered.
     * @throws ReadException If data is unable to be read.
     */
    public function read();

    /**
     * Read and return all data rows.
     *
     * @return array<array>  All data rows.
     * @throws ReadException If data is unable to be read.
     */
    public function readAll();
}
