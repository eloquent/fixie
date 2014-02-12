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
 * A writable data handle that writes rows in the 'expanded' style, but does not
 * align row values.
 *
 * Only useful if the data should be output in a similar way to typical YAML
 * renderers.
 */
class ExpandedFixtureWriteHandle extends AbstractWriteHandle
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
            $this->isMap = range(0, count($this->columnNames) - 1) !== $this->columnNames;
        }

        $this->writeRow($this->projectRow($this->columnNames, $row));
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
            "%s%s\n",
            $this->firstRow ? '' : "\n",
            implode("\n", $lines)
        ));
        $this->firstRow = false;
    }

    private $columnNames;
    private $isMap;
    private $firstRow = true;
}
