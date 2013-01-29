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

class FixtureWriter implements FixtureWriterInterface
{
    /**
     * @param string|null $handleClassName
     */
    public function __construct($handleClassName = null)
    {
        if (null === $handleClassName) {
            $handleClassName = __NAMESPACE__.'\AlignedCompactFixtureWriteHandle';
        }

        $this->handleClassName = $handleClassName;
    }

    /**
     * @return string
     */
    public function handleClassName()
    {
        return $this->handleClassName;
    }

    /**
     * @param string $path
     *
     * @return WriteHandleInterface
     */
    public function openFile($path)
    {
        $className = $this->handleClassName();

        return new $className(
            null,
            $path
        );
    }

    /**
     * @param stream{writable: true} $stream
     * @param string|null            $path
     *
     * @return WriteHandleInterface
     */
    public function openStream($stream, $path = null)
    {
        $className = $this->handleClassName();

        return new $className(
            $stream,
            $path
        );
    }

    private $handleClassName;
}
