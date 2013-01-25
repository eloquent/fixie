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

use Exception;
use LogicException;

final class EmptyHandleException extends LogicException
{
    /**
     * @param Exception|null $previous
     */
    public function __construct(Exception $previous = null)
    {
        parent::__construct('Stream and/or path must be provided.', 0, $previous);
    }
}
