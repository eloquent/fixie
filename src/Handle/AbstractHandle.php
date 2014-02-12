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

use ErrorException;
use Icecave\Isolator\Isolator;

/**
 * An abstract base class for implementing data handles.
 */
abstract class AbstractHandle implements HandleInterface
{
    /**
     * Construct a new stream handle.
     *
     * @param stream|null   $stream   The native stream handle, or null to create lazily from the filesystem path.
     * @param string|null   $path     The filesystem path, or null if the path is unknown.
     * @param Isolator|null $isolator The isolator to use.
     */
    public function __construct(
        $stream = null,
        $path = null,
        Isolator $isolator = null
    ) {
        if (null === $stream && null === $path) {
            throw new Exception\EmptyHandleException;
        }

        $this->stream = $stream;
        $this->path = $path;
        $this->isolator = Isolator::get($isolator);
        $this->isClosed = false;
    }

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
    public function stream()
    {
        if ($this->isClosed()) {
            throw new Exception\ClosedHandleException($this->path());
        }

        if (null === $this->stream) {
            $this->stream = $this->openStream();
        }

        return $this->stream;
    }

    /**
     * Get the related filesystem path.
     *
     * @return string|null The filesystem path, or null if the path is unknown.
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Returns true if this handle is closed.
     *
     * @return boolean True if this handle is closed.
     */
    public function isClosed()
    {
        return $this->isClosed;
    }

    /**
     * Close this handle.
     *
     * @throws Exception\ReadException         If closing the handle fails.
     * @throws Exception\ClosedHandleException If this handle is closed.
     */
    public function close()
    {
        if ($this->isClosed()) {
            throw new Exception\ClosedHandleException($this->path());
        }

        if (null === $this->stream) {
            $this->isClosed = true;
        } else {
            try {
                $this->isolator()->fclose($this->stream());
            } catch (ErrorException $e) {
                throw new Exception\ReadException($this->path(), $e);
            }

            $this->isClosed = true;
        }
    }

    /**
     * Set the closed status of this handle.
     *
     * @param boolean $isClosed True if this handle is closed.
     */
    protected function setIsClosed($isClosed)
    {
        $this->isClosed = $isClosed;
    }

    /**
     * Get the isolator.
     *
     * @return Isolator The isolator.
     */
    protected function isolator()
    {
        return $this->isolator;
    }

    /**
     * Open a stream to the file at the pre-defined path.
     *
     * @return stream                         The stream.
     * @throws Exception\IoExceptionInterface If opening the stream fails.
     */
    abstract protected function openStream();

    private $stream;
    private $path;
    private $isolator;
    private $isClosed;
}
