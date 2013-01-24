<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader;

class ExpandedHandle extends AbstractHandle
{
    protected function parseHeader()
    {
        $this->rewindStream();
        $line = $this->readNonEmptyLine();
        $this->startLine = 1;

        if (null === $line) {
            $this->columnNames = array();
            $this->emptyData = true;
        } elseif ('- ' === substr($line, 0, 2)) {
            $this->currentLine = $line;
            $this->columnNames = $this->parseRowColumnNames();
            $this->emptyData = false;
        } else {
            throw new Exception\ReadException($this->path());
        }

        $this->rewindHandle();
    }

    /**
     * @return array
     */
    protected function parseRowColumnNames()
    {
        return array_keys($this->readRow());
    }

    /**
     * @return array|null
     */
    protected function fetchRow()
    {
        $data = $this->readRow();
        if (null === $data) {
            return null;
        }
        if (array_keys($data) !== $this->columnNames) {
            throw new Exception\ReadException($this->path());
        }

        return $data;
    }

    /**
     * @return array|null
     */
    protected function readRow()
    {
        if (null === $this->currentLine) {
            return null;
        }

        $lines = array();
        do {
            $lines[] = $this->currentLine;
            $this->readLine();
        } while (
            null !== $this->currentLine &&
            '- ' !== substr($this->currentLine, 0, 2)
        );

        $data = $this->parseYamlArray(implode($lines));
        if (
            array(0) !== array_keys($data) ||
            !is_array($data[0])
        ) {
            throw new Exception\ReadException($this->path());
        }

        return $data[0];
    }

    /**
     * @return string|null
     */
    protected function readLine()
    {
        $this->currentLine = parent::readLine();

        return $this->currentLine;
    }

    private $emptyData;
    private $currentLine;
}
