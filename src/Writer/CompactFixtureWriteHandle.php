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

use Eloquent\Fixie\Handle\Exception\ClosedHandleException;
use Eloquent\Fixie\Handle\Exception\ReadException;
use Eloquent\Fixie\Handle\Exception\WriteException;

/**
 * A writable data handle that writes rows in the 'compact' style, using the
 * minimal amount of whitespace.
 *
 * This variant is excellent for any data size, but is not as good for human
 * readability as other options. If human readability is not an issue, use this
 * variant.
 */
class CompactFixtureWriteHandle extends AbstractWriteHandle
{
    /**
     * Write a single data row.
     *
     * @param array<string,mixed> $row The data row.
     *
     * @throws WriteException If data is unable to be written.
     */
    public function write(array $row)
    {
        if (null === $this->columnNames) {
            $this->columnNames = array_keys($row);
            $this->writeHeader($this->columnNames);
        }

        $this->writeRow($this->projectRow($this->columnNames, $row));
    }

    /**
     * Close this handle.
     *
     * @throws ReadException         If closing the handle fails.
     * @throws ClosedHandleException If this handle is closed.
     */
    public function close()
    {
        if (!$this->isClosed() && null !== $this->columnNames) {
            $this->writeFooter();
        }

        parent::close();
    }

    /**
     * Write the header.
     *
     * @param array<integer,string> $columnNames The column names.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeHeader(array $columnNames)
    {
        if (range(0, count($columnNames) - 1) !== $columnNames) {
            $this->writeData(
                sprintf("columns: %s\n", $this->renderer()->dump($columnNames))
            );
        }

        $this->writeData("data: [\n");
    }

    /**
     * Write a single data row.
     *
     * @param array<integer,mixed> $row The projected data row to write.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeRow(array $row)
    {
        $this->writeData(
            sprintf("%s,\n", $this->renderer()->dump(array_values($row)))
        );
    }

    /**
     * Write the footer.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeFooter()
    {
        $this->writeData("]\n");
    }

    private $columnNames;
}
