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

use Eloquent\Fixie\Handle\Exception\WriteException;

/**
 * A writable data handle that writes rows in the 'expanded' style, and aligns
 * row values.
 *
 * A versatile variant produces a much more vertically elongated output. Good
 * for both human readability and memory usage.
 */
class AlignedExpandedFixtureWriteHandle extends AbstractWriteHandle
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
            $this->isMap =
                range(0, count($this->columnNames) - 1) !==
                $this->columnNames;

            if ($this->isMap) {
                $this->columnNamesRendered = array_map(
                    array($this->renderer(), 'dump'),
                    $this->columnNames
                );

                $this->columnSize = 0;
                foreach ($this->columnNamesRendered as $columnName) {
                    $columnSize = mb_strlen($columnName, 'UTF-8');
                    if ($columnSize > $this->columnSize) {
                        $this->columnSize = $columnSize;
                    }
                }
            }
        }

        $this->writeRow($this->projectRow($this->columnNames, $row));
    }

    /**
     * Write a single projected data row.
     *
     * @param array<integer,mixed> $row The projected data row.
     *
     * @throws WriteException If data is unable to be written.
     */
    protected function writeRow(array $row)
    {
        $lines = array();
        if (!$this->isMap) {
            $lines[] = '-';
        }

        foreach ($this->columnNames as $index => $columnName) {
            $columnSize = mb_strlen($columnName, 'UTF-8');

            if ($this->isMap) {
                $lines[] = sprintf(
                    '%s %s:%s %s',
                    array() === $lines ? '-' : ' ',
                    $this->renderer()->dump($columnName),
                    str_repeat(' ', $this->columnSize - $columnSize),
                    $this->renderer()->dump($row[$index])
                );
            } else {
                $lines[] = sprintf(
                    '  - %s',
                    $this->renderer()->dump($row[$index])
                );
            }
        }

        $this->writeData(
            sprintf(
                "%s%s\n",
                $this->firstRow ? '' : "\n",
                implode("\n", $lines)
            )
        );
        $this->firstRow = false;
    }

    private $columnNames;
    private $columnNamesRendered;
    private $isMap;
    private $columnSize;
    private $firstRow = true;
}
