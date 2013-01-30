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

class AlignedCompactFixtureWriteHandle extends AbstractWriteHandle
{
    /**
     * @param array $row
     */
    public function write(array $row)
    {
        if (null === $this->columnNames) {
            $this->columnNames = array_keys($row);
            $this->columnNamesRendered = array_map(
                array($this->renderer(), 'dump'),
                $this->columnNames
            );
            $this->isMap = range(0, count($this->columnNames) - 1) !== $this->columnNames;
        }

        $row = $this->projectRow($this->columnNames, $row);
        $this->rows[] = array_map(
            array($this->renderer(), 'dump'),
            $row
        );
    }

    public function close()
    {
        if (!$this->isClosed()) {
            $this->writeAligned();
        }

        parent::close();
    }

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
     * @param array $columnSizes
     */
    protected function writeHeader(array $columnSizes)
    {
        if ($this->isMap) {
            $this->writeData(sprintf(
                "columns:\n %s\n",
                $this->renderRowAligned($columnSizes, $this->columnNamesRendered)
            ));
        }

        $this->writeData("data: [\n");
    }

    /**
     * @param array $columnSizes
     * @param array $row
     */
    protected function writeRow(array $columnSizes, array $row)
    {
        $this->writeData(sprintf(
            "%s%s,\n",
            $this->isMap ? ' ' : '',
            $this->renderRowAligned($columnSizes, $row)
        ));
    }

    protected function writeFooter()
    {
        $this->writeData("]\n");
    }

    /**
     * @param array<integer,mixed> $rows
     *
     * @return array<integer>
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
     * @param array<integer> &$columnSizes
     * @param array          $values
     */
    protected function alterColumnSizes(array &$columnSizes, array $values)
    {
        $i = 0;
        foreach ($values as $value) {
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
     * @param array $columnSizes
     * @param array $row
     *
     * @return string
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
