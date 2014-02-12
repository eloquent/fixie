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
 * A writable data handle that writes rows in the 'compact' style, and keeps
 * column headers and row values aligned with each other.
 *
 * This style is excellent for human readability but poor for large data sets as
 * the data must be buffered in memory. Unless the maximum data size is known in
 * advance, it is recommended to use the SwitchingCompactFixtureWriteHandle
 * instead.
 *
 * @see SwitchingCompactFixtureWriteHandle
 */
class AlignedCompactFixtureWriteHandle extends AbstractWriteHandle
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
            $this->columnNamesRendered = array_map(
                array($this->renderer(), 'dump'),
                $this->columnNames
            );
            $this->isMap =
                range(0, count($this->columnNames) - 1) !==
                $this->columnNames;
        }

        $row = $this->projectRow($this->columnNames, $row);
        $this->rows[] = array_map(array($this->renderer(), 'dump'), $row);
    }

    /**
     * Close this handle.
     *
     * @throws ReadException         If closing the handle fails.
     * @throws ClosedHandleException If this handle is closed.
     */
    public function close()
    {
        if (!$this->isClosed()) {
            $this->writeAligned();
        }

        parent::close();
    }

    /**
     * Write the entire data fixture with aligned column values.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeAligned()
    {
        if (count($this->rows) < 1) {
            return;
        }

        $columnSizes = $this->columnSizes($this->rows);
        $this->writeHeader($columnSizes);
        foreach ($this->rows as $row) {
            $this->writeRow($columnSizes, $row);
        }
        $this->writeFooter();
    }

    /**
     * Write the header.
     *
     * @param array<integer,integer> $columnSizes The column sizes to use.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeHeader(array $columnSizes)
    {
        if ($this->isMap) {
            $this->writeData(
                sprintf(
                    "columns:\n %s\n",
                    $this->renderRowAligned(
                        $columnSizes,
                        $this->columnNamesRendered
                    )
                )
            );
        }

        $this->writeData("data: [\n");
    }

    /**
     * Write a single data row.
     *
     * @param array<integer,integer> $columnSizes The column sizes to use.
     * @param array<integer,mixed>   $row         The projected data row to write.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeRow(array $columnSizes, array $row)
    {
        $this->writeData(sprintf(
            "%s%s,\n",
            $this->isMap ? ' ' : '',
            $this->renderRowAligned($columnSizes, $row)
        ));
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

    /**
     * Calculate the column sizes.
     *
     * @param array<integer,mixed> $rows The projected data rows.
     *
     * @return array<integer,integer> The column sizes.
     */
    protected function columnSizes(array $rows)
    {
        $columnSizes = array();
        $this->alterColumnSizes($columnSizes, $this->columnNamesRendered);
        foreach ($rows as $row) {
            $this->alterColumnSizes($columnSizes, $row);
        }

        return $columnSizes;
    }

    /**
     * Inspect the supplied data row, and alter the column sizes as necessary.
     *
     * @param array<integer,integer> &$columnSizes The current column sizes.
     * @param array<integer,mixed>   $row          The projected data row.
     */
    protected function alterColumnSizes(array &$columnSizes, array $row)
    {
        $i = 0;
        foreach ($row as $value) {
            $valueSize = mb_strlen($value, 'UTF-8');
            if (
                !array_key_exists($i, $columnSizes) ||
                $valueSize > $columnSizes[$i]
            ) {
                $columnSizes[$i] = $valueSize;
            }

            $i ++;
        }
    }

    /**
     * Render a data row with aligned column values.
     *
     * @param array<integer,integer> $columnSizes The column sizes to use.
     * @param array<integer,mixed>   $row         The projected data row to write.
     *
     * @return string The rendered data row.
     */
    protected function renderRowAligned(array $columnSizes, array $row)
    {
        $columns = array();
        $rowSize = count($row);
        foreach ($row as $index => $value) {
            $terminal = $index > $rowSize - 2;
            $columns[] = sprintf(
                '%s%s%s',
                $value,
                $terminal ? '' : ',',
                str_repeat(
                    ' ',
                    $columnSizes[$index] - mb_strlen($value, 'UTF-8')
                )
            );
        }

        return sprintf('[%s]', implode(' ', $columns));
    }

    private $rows = array();
    private $columnNames;
    private $columnNamesRendered;
    private $isMap;
}
