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
 * The handle is already closed.
 */
final class ClosedHandleException extends Exception
{
    /**
     * Construct a new closed handle exception.
     *
     * @param string|null    $path  The filesystem path, or null if the path is unknown.
     * @param Exception|null $cause The cause, if available.
     */
    public function __construct($path = null, Exception $cause = null)
    {
        $this->path = $path;

        if (null === $path) {
            $message = 'Handle already closed.';
        } else {
            $message = sprintf(
                'Handle to %s already closed.',
                var_export($path, true)
            );
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
