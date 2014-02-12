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

/**
 * Neither a stream nor path was provided.
 */
final class EmptyHandleException extends Exception
{
    /**
     * Construct a new empty handle exception.
     *
     * @param Exception|null $cause The cause, if available.
     */
    public function __construct(Exception $cause = null)
    {
        parent::__construct('Stream and/or path must be provided.', 0, $cause);
    }
}
