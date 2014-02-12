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
 * The interface implemented by factories that produce data handles.
 */
interface HandleFactoryInterface
{
    /**
     * Create a data handle for the file at the specified path.
     *
     * @param string $path The path to the file.
     *
     * @return HandleInterface The newly created handle.
     */
    public function openFile($path);

    /**
     * Create a data handle for the supplied native stream handle.
     *
     * @param stream      $stream The native stream handle.
     * @param string|null $path   The filesystem path, or null if the path is unknown.
     *
     * @return HandleInterface The newly created handle.
     */
    public function openStream($stream, $path = null);
}
