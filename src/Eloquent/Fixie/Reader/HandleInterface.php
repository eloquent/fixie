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

use Iterator;

interface HandleInterface extends Iterator
{
    /**
     * @return stream {readable: true}
     */
    public function stream();

    /**
     * @return string|null
     */
    public function path();
}