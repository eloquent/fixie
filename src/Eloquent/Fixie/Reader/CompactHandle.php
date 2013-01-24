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

class CompactHandle extends AbstractHandle
{
    protected function parseHeader()
    {
        $this->rewindStream();
        $line0 = $this->readNonEmptyLine();

        if (null === $line0) {
            $this->columnNames = array();
            $this->startLine = 0;
            $this->emptyData = true;
        } elseif ("data: [\n" === $line0) {
            $this->columnNames = $this->parseRowColumnNames($this->readNonEmptyLine());
            $this->startLine = 1;
            $this->emptyData = false;
        } elseif ('columns: [' === substr($line0, 0, 10)) {
            if ("data: [\n" !== $this->readNonEmptyLine()) {
                throw new Exception\ReadException($this->path());
            }

            $this->columnNames = $this->parseHeaderColumnNames($line0);
            $this->startLine = 2;
            $this->emptyData = false;
        } else {
            throw new Exception\ReadException($this->path());
        }

        $this->expectedKeys = range(0, count($this->columnNames) - 1);
        $this->rewindHandle();
    }

    /**
     * @param string|null $line
     *
     * @return array
     */
    protected function parseRowColumnNames($line)
    {
        if ($this->lineEquals($line, ']')) {
            return array();
        }
        if (null === $line) {
            throw new Exception\ReadException($this->path());
        }

        return array_keys($this->parseYamlArray($this->stripRowEnd($line)));
    }

    /**
     * @param string $line
     *
     * @return array
     */
    protected function parseHeaderColumnNames($line)
    {
        $data = $this->parseYamlArray($line);

        return $data['columns'];
    }

    /**
     * @return array|null
     */
    protected function fetchRow()
    {
        if (null === $this->position) {
            return null;
        }
        $line = $this->readNonEmptyLine();
        if ($this->lineEquals($line, ']')) {
            return null;
        }
        if (null === $line) {
            if ($this->emptyData) {
                return null;
            }

            throw new Exception\ReadException($this->path());
        }

        $data = $this->parseYamlArray($this->stripRowEnd($line));
        if (array_keys($data) !== $this->expectedKeys) {
            throw new Exception\ReadException($this->path());
        }

        return array_combine($this->columnNames, $data);
    }

    /**
     * @param string $line
     *
     * @return string
     */
    protected function stripRowEnd($line)
    {
        if (",\n" !== substr($line, -2)) {
            throw new Exception\ReadException($this->path());
        }

        return substr($line, 0, -2);
    }

    private $emptyData;
    private $expectedKeys;
}
