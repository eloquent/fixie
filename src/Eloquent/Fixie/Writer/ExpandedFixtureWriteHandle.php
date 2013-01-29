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

class ExpandedFixtureWriteHandle extends AbstractWriteHandle
{
    /**
     * @param array $row
     */
    public function write(array $row)
    {
        if (null === $this->columnNames) {
            $this->columnNames = array_keys($row);
            $this->isMap = range(0, count($this->columnNames) - 1) !== $this->columnNames;
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
            if ($this->isMap) {
                $lines[] = sprintf(
                    '%s %s: %s',
                    array() === $lines ? '-' : ' ',
                    $this->renderer()->dump($columnName),
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
            "%s\n",
            implode("\n", $lines)
        ));
    }

    private $columnNames;
    private $isMap;
}
