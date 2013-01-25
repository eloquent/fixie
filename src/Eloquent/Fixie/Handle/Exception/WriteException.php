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
use RuntimeException;

final class WriteException extends RuntimeException
{
    /**
     * @param string|null    $path
     * @param Exception|null $previous
     */
    public function __construct($path = null, Exception $previous = null)
    {
        $this->path = $path;

        if (null === $path) {
            $message = 'Unable to write data to stream.';
        } else {
            $message = sprintf("Unable to write data to file '%s'.", $path);
        }

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string|null
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
