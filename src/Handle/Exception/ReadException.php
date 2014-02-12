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
 * Unable to read from the stream.
 */
final class ReadException extends Exception implements IoExceptionInterface
{
    /**
     * Construct a new read exception.
     *
     * @param string|null    $path  The filesystem path, or null if the path is unknown.
     * @param Exception|null $cause The cause, if available.
     */
    public function __construct($path = null, Exception $cause = null)
    {
        $this->path = $path;

        if (null === $path) {
            $message = 'Unable to read data from stream.';
        } else {
            $message = sprintf("Unable to read data from file '%s'.", $path);
        }

        parent::__construct($message, 0, $cause);
    }

    /**
     * Get the filesystem path associated with the handle.
     *
     * @return string|null The filesystem path, or null if the path is unknown.
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
