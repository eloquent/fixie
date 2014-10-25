<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Fixie\Handle\AbstractHandle;
use Eloquent\Fixie\Handle\Exception\ClosedHandleException;
use Eloquent\Fixie\Handle\Exception\IoExceptionInterface;
use Eloquent\Fixie\Handle\Exception\ReadException;
use Eloquent\Fixie\Handle\Exception\WriteException;
use ErrorException;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Inline;

/**
 * A writable data handle that produces an appropriate output format depending
 * on the size of the data to be written.
 *
 * This variant will buffer up to an approximate given data size (defaults to
 * 10MiB). If the data written is within the size limit, the output will be that
 * produced by the AlignedCompactFixtureWriteHandle. If the size limit is
 * exceeded, this variant will switch to unbuffered output using the
 * CompactFixtureWriteHandle.
 *
 * @see AlignedCompactFixtureWriteHandle
 * @see CompactFixtureWriteHandle
 */
class SwitchingCompactFixtureWriteHandle extends AbstractHandle implements
    WriteHandleInterface
{
    /**
     * Construct a new switching compact writable data handle.
     *
     * @param stream|null   $stream   The native stream handle, or null to create lazily from the filesystem path.
     * @param string|null   $path     The filesystem path, or null if the path is unknown.
     * @param Inline|null   $renderer The YAML renderer to use.
     * @param Isolator|null $isolator The isolator to use.
     */
    public function __construct(
        $stream = null,
        $path = null,
        Inline $renderer = null,
        Isolator $isolator = null
    ) {
        parent::__construct($stream, $path, $isolator);

        if (null === $renderer) {
            $renderer = new Inline();
        }

        $this->renderer = $renderer;

        $this->bufferSize = 10485760;
        $this->dataSize = 0;
        $this->rows = array();
        $this->dataWritten = false;
    }

    /**
     * Get the YAML renderer.
     *
     * @return Inline The YAML renderer.
     */
    public function renderer()
    {
        return $this->renderer;
    }

    /**
     * Set the size at which the output will switch from aligned to compact
     * format.
     *
     * @param integer $bufferSize The buffer size in bytes.
     */
    public function setBufferSize($bufferSize)
    {
        $this->bufferSize = $bufferSize;
    }

    /**
     * Get the size at which the output will switch from aligned to compact
     * format.
     *
     * @return integer The buffer size in bytes.
     */
    public function bufferSize()
    {
        return $this->bufferSize;
    }

    /**
     * Write a single data row.
     *
     * @param array<string,mixed> $row The data row.
     *
     * @throws WriteException If data is unable to be written.
     */
    public function write(array $row)
    {
        $this->dataWritten = true;

        if (null !== $this->handle) {
            $this->handle->write($row);

            return;
        }

        $this->rows[] = $row;
        $this->dataSize += $this->rowSize($row);

        if ($this->dataSize > $this->bufferSize) {
            $this->handle = $this->createHandle();
            $this->handle->writeAll($this->rows);
            $this->rows = array();
        }
    }

    /**
     * Write a sequence of data rows.
     *
     * @param array<array<string,mixed>> $rows The data rows.
     *
     * @throws WriteException If data is unable to be written.
     */
    public function writeAll(array $rows)
    {
        foreach ($rows as $row) {
            $this->write($row);
        }
    }

    /**
     * Close this handle.
     *
     * @throws ReadException         If closing the handle fails.
     * @throws ClosedHandleException If this handle is closed.
     */
    public function close()
    {
        if ($this->dataWritten) {
            if (null === $this->handle) {
                $this->handle = $this->createAlignedHandle();
                $this->handle->writeAll($this->rows);
                $this->handle->close();
            } else {
                $this->handle->close();
            }
        } else {
            parent::close();
        }

        $this->setIsClosed(true);
    }

    /**
     * Open a stream to the file at the pre-defined path.
     *
     * @return stream               The stream.
     * @throws IoExceptionInterface If opening the stream fails.
     */
    protected function openStream()
    {
        try {
            $stream = $this->isolator()->fopen(
                $this->path(),
                'wb'
            );
        } catch (ErrorException $e) {
            throw new WriteException($this->path(), $e);
        }

        return $stream;
    }

    /**
     * Calculate the appropximate data size of a row.
     *
     * @param array<integer,mixed> $row The data row.
     *
     * @return integer The data size in bytes.
     */
    protected function rowSize(array $row)
    {
        return strlen(
            sprintf('[%s]', implode(', ', array_map('strval', $row)))
        );
    }

    /**
     * Create a new compact style write handle.
     *
     * @return CompactFixtureWriteHandle The newly created write handle.
     */
    protected function createHandle()
    {
        return new CompactFixtureWriteHandle(
            $this->stream(),
            $this->path(),
            $this->renderer(),
            $this->isolator()
        );
    }

    /**
     * Create a new aligned style write handle.
     *
     * @return AlignedCompactFixtureWriteHandle The newly created write handle.
     */
    protected function createAlignedHandle()
    {
        return new AlignedCompactFixtureWriteHandle(
            $this->stream(),
            $this->path(),
            $this->renderer(),
            $this->isolator()
        );
    }

    private $renderer;
    private $bufferSize;
    private $rows;
    private $dataSize;
    private $handle;
    private $dataWritten;
}
