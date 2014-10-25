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
use Eloquent\Fixie\Handle\Exception\IoExceptionInterface;
use Eloquent\Fixie\Handle\Exception\WriteException;
use ErrorException;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Inline;

/**
 * An abstract base class for implementing writable data handles.
 */
abstract class AbstractWriteHandle extends AbstractHandle implements WriteHandleInterface
{
    /**
     * Construct a new writable data handle.
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
     * Open a stream to the file at the pre-defined path.
     *
     * @return stream               The stream.
     * @throws IoExceptionInterface If opening the stream fails.
     */
    protected function openStream()
    {
        try {
            $stream = $this->isolator()->fopen($this->path(), 'wb');
        } catch (ErrorException $e) {
            throw new WriteException($this->path(), $e);
        }

        return $stream;
    }

    /**
     * Write data to the native stream handle.
     *
     * @param string $data The data to write.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeData($data)
    {
        try {
            $this->isolator()->fwrite($this->stream(), $data);
        } catch (ErrorException $e) {
            throw new WriteException($this->path(), $e);
        }
    }

    /**
     * Project the values in the supplied associative data row to their correct
     * indices.
     *
     * @param array<integer,string> $columnNames The column names in their defined order.
     * @param array<string,mixed>   $row         The data row.
     *
     * @return array<integer,mixed> The projected data row.
     */
    protected function projectRow(array $columnNames, array $row)
    {
        $projected = array();
        foreach ($columnNames as $columnName) {
            if (!array_key_exists($columnName, $row)) {
                throw new WriteException($this->path());
            }

            $projected[] = $row[$columnName];
        }

        return $projected;
    }

    private $renderer;
}
