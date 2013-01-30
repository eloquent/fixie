<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Writer;

use Eloquent\Fixie\Handle\AbstractHandle;
use Eloquent\Fixie\Handle\Exception\WriteException;
use ErrorException;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Inline;

class SwitchingCompactFixtureWriteHandle extends AbstractHandle implements WriteHandleInterface
{
    /**
     * @param stream{writable: true}|null $stream
     * @param string|null                 $path
     * @param Inline|null                 $renderer
     * @param Isolator|null               $isolator
     */
    public function __construct(
        $stream = null,
        $path = null,
        Inline $renderer = null,
        Isolator $isolator = null
    ) {
        parent::__construct(
            $stream,
            $path,
            $isolator
        );

        if (null === $renderer) {
            $renderer = new Inline;
        }

        $this->renderer = $renderer;

        $this->bufferSize = 10485760;
        $this->dataSize = 0;
        $this->rows = array();
        $this->dataWritten = false;
    }

    /**
     * @return Inline
     */
    public function renderer()
    {
        return $this->renderer;
    }

    /**
     * @param integer $bufferSize
     */
    public function setBufferSize($bufferSize)
    {
        $this->bufferSize = $bufferSize;
    }

    /**
     * @return integer
     */
    public function bufferSize()
    {
        return $this->bufferSize;
    }

    /**
     * @param array $row
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
     * @param array<array> $rows
     */
    public function writeAll(array $rows)
    {
        foreach ($rows as $row) {
            $this->write($row);
        }
    }

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
     * @return stream
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
     * @param array $row
     *
     * @return integer
     */
    protected function rowSize(array $row)
    {
        return strlen(sprintf(
            '[%s]',
            implode(', ', array_map('strval', $row))
        ));
    }

    /**
     * @return CompactFixtureWriteHandle
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
     * @return AlignedCompactFixtureWriteHandle
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
