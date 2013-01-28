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

use Eloquent\Fixie\Handle\HandleInterface;
use Iterator;

interface ReadHandleInterface extends HandleInterface, Iterator
{
    /**
     * @return array|null
     */
    public function read();

    /**
     * @return array<array>
     */
    public function readAll();
}
