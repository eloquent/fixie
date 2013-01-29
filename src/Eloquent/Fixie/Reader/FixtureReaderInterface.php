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

interface FixtureReaderInterface
{
    /**
     * @param string $path
     *
     * @return ReadHandleInterface
     */
    public function openFile($path);

    /**
     * @param stream{readable: true} $stream
     * @param string|null            $path
     *
     * @return ReadHandleInterface
     */
    public function openStream($stream, $path = null);
}
