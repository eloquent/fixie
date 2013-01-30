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

class AlignedExpandedFixtureWriteHandle extends AbstractWriteHandle
{
    /**
     * @param array $row
     */
    public function write(array $row)
    {
        if (null === $this->columnNames) {
            $this->columnNames = array_keys($row);
            $this->isMap = range(0, count($this->columnNames) - 1) !== $this->columnNames;

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
     * @param array $row
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

        $this->writeData(sprintf(
            "%s%s\n",
            $this->firstRow ? '' : "\n",
            implode("\n", $lines)
        ));
        $this->firstRow = false;
    }

    private $columnNames;
    private $columnNamesRendered;
    private $isMap;
    private $columnSize;
    private $firstRow = true;
}
