<?php // @codeCoverageIgnoreStart

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

interface FixtureWriterInterface
{
    /**
     * @param string $path
     *
     * @return WriteHandleInterface
     */
    public function openFile($path);

    /**
     * @param stream{readable: true} $stream
     *
     * @return WriteHandleInterface
     */
    public function openStream($stream);
}
