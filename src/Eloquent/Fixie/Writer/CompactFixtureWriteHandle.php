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

class CompactFixtureWriteHandle extends AbstractWriteHandle
{
    /**
     * @param array $row
     */
    public function write(array $row)
    {
        if (null === $this->columnNames) {
            $this->columnNames = array_keys($row);
            $this->writeHeader($this->columnNames);
        }

        $this->writeRow($this->projectRow($this->columnNames, $row));
    }

    public function close()
    {
        if (!$this->isClosed() && null !== $this->columnNames) {
            $this->writeFooter();
        }

        parent::close();
    }

    /**
     * @param array $columnNames
     */
    protected function writeHeader(array $columnNames)
    {
        if (range(0, count($columnNames) - 1) !== $columnNames) {
            $this->writeData(sprintf(
                "columns: %s\n",
                $this->renderer()->dump($columnNames)
            ));
        }

        $this->writeData("data: [\n");
    }

    /**
     * @param array $row
     */
    protected function writeRow(array $row)
    {
        $this->writeData(sprintf(
            "%s,\n",
            $this->renderer()->dump(array_values($row))
        ));
    }

    protected function writeFooter()
    {
        $this->writeData("]\n");
    }

    private $columnNames;
}
