<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Handle;

/**
 * The interface implemented by data handles.
 */
interface HandleInterface
{
    /**
     * Get the native stream handle.
     *
     * If lazy stream opening is in use, this method will cause the initial
     * stream open operation to occur.
     *
     * @return stream                          The stream handle.
     * @throws Exception\IoExceptionInterface  If opening the stream fails.
     * @throws Exception\ClosedHandleException If this handle is closed.
     */
    public function stream();

    /**
     * Get the related filesystem path.
     *
     * @return string|null The filesystem path, or null if the path is unknown.
     */
    public function path();

    /**
     * Returns true if this handle is closed.
     *
     * @return boolean True if this handle is closed.
     */
    public function isClosed();

    /**
     * Close this handle.
     *
     * @throws Exception\ReadException         If closing the handle fails.
     * @throws Exception\ClosedHandleException If this handle is closed.
     */
    public function close();
}
