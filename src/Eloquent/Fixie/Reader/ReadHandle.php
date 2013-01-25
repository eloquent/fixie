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

use Eloquent\Fixie\Handle\AbstractHandle;
use Eloquent\Fixie\Handle\Exception\ReadException;
use ErrorException;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class ReadHandle extends AbstractHandle implements ReadHandleInterface
{
    /**
     * @param stream{readable: true}|null $stream
     * @param string|null                 $path
     * @param Parser|null                 $parser
     * @param Isolator|null               $isolator
     */
    public function __construct(
        $stream = null,
        $path = null,
        Parser $parser = null,
        Isolator $isolator = null
    ) {
        parent::__construct(
            $stream,
            $path,
            $isolator
        );

        if (null === $parser) {
            $parser = new Parser;
        }

        $this->parser = $parser;
        $this->rewindOffset = 0;
        $this->isExhausted = false;
        $this->isExpanded = false;
    }

    /**
     * @return Parser
     */
    public function parser()
    {
        return $this->parser;
    }

    public function rewind()
    {
        try {
            $this->isolator()->fseek($this->stream(), $this->rewindOffset);
        } catch (ErrorException $e) {
            throw new ReadException($this->path(), $e);
        }

        $this->current = null;
        $this->index = null;
        $this->isExhausted = false;
        $this->currentLine = null;

        if ($this->isExpanded) {
            $this->readLine();
        }
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        $this->populateCurrent();

        return null !== $this->index;
    }

    /**
     * @return array|null
     */
    public function current()
    {
        $this->populateCurrent();

        return $this->current;
    }

    /**
     * @return integer|null
     */
    public function key()
    {
        $this->populateCurrent();

        return $this->index;
    }

    public function next()
    {
        $this->current = null;
    }

    /**
     * @return stream
     */
    protected function openStream()
    {
        try {
            $stream = $this->isolator()->fopen(
                $this->path(),
                'rb'
            );
        } catch (ErrorException $e) {
            throw new ReadException($this->path(), $e);
        }

        return $stream;
    }

    protected function populateCurrent()
    {
        if ($this->isExhausted || null !== $this->current) {
            return;
        }

        $this->current = $this->parseRow();

        if (null === $this->current) {
            $this->isExhausted = true;
            $this->index = null;
        } elseif (null === $this->index) {
            $this->index = 0;
        } else {
            $this->index ++;
        }
    }

    /**
     * @return array|null
     */
    protected function parseRow()
    {
        if (null === $this->columnNames) {
            return $this->parseFirstRow();
        }

        return $this->parseSubsequentRow();
    }

    /**
     * @return array|null
     */
    protected function parseFirstRow()
    {
        $line = $this->readNonEmptyLine();
        if (null === $line) {
            return null;
        }

        $this->rewindOffset = $this->streamPosition() - strlen($line);

        if ('columns: [' === substr($line, 0, 10)) {
            $this->isExpanded = false;
            $this->columnNames = $this->parseColumnNamesHeader($line);
            $this->expectedRowKeys = range(0, count($this->columnNames) - 1);

            $line = $this->readNonEmptyLine();
            if ("data: [\n" !== $line) {
                throw new ReadException($this->path());
            }
            $this->rewindOffset = $this->streamPosition();

            $row = $this->parseSubsequentRow();
        } elseif ('- ' === substr($line, 0, 2)) {
            $this->isExpanded = true;
            $row = $this->parseExpandedRowYaml($this->readExpandedRowLines());
            $this->columnNames = $this->expectedRowKeys = array_keys($row);
        } elseif ('data: [' === trim($line)) {
            $this->isExpanded = false;
            $this->rewindOffset = $this->streamPosition();
            $line = $this->readNonEmptyLine();
            if (null === $line) {
                throw new ReadException($this->path());
            }

            if (']' === trim($line)) {
                $row = null;
                $this->columnNames = $this->expectedRowKeys = array();
            } else {
                $row = $this->parseCompactRowYaml($line);
                $this->columnNames = array_keys($row);
                $this->expectedRowKeys = range(0, count($this->columnNames) - 1);
            }
        } else {
            throw new ReadException($this->path());
        }

        return $row;
    }

    /**
     * @return array|null
     */
    protected function parseSubsequentRow()
    {
        if ($this->isExpanded) {
            return $this->parseSubsequentExpandedRow();
        }

        return $this->parseSubsequentCompactRow();
    }

    /**
     * @return array|null
     */
    protected function parseSubsequentCompactRow()
    {
        $line = $this->readNonEmptyLine();
        if (null === $line) {
            throw new ReadException($this->path());
        }
        if (']' === trim($line)) {
            return null;
        }

        $row = $this->parseCompactRowYaml($line);
        if (array_keys($row) !== $this->expectedRowKeys) {
            throw new ReadException($this->path());
        }

        return array_combine($this->columnNames, $row);
    }

    /**
     * @return array|null
     */
    protected function parseSubsequentExpandedRow()
    {
        $data = $this->readExpandedRowLines();
        if (null === $data) {
            return null;
        }

        $row = $this->parseExpandedRowYaml($data);
        if (array_keys($row) !== $this->expectedRowKeys) {
            throw new ReadException($this->path());
        }

        return $row;
    }

    /**
     * @param string $yaml
     *
     * @return array
     */
    protected function parseCompactRowYaml($yaml)
    {
        if (",\n" !== substr($yaml, -2)) {
            throw new ReadException($this->path());
        }

        return $this->parseArrayYaml(substr($yaml, 0, -2));
    }

    /**
     * @param string $yaml
     *
     * @return array
     */
    protected function parseExpandedRowYaml($yaml)
    {
        $data = $this->parseArrayYaml($yaml);
        if (!is_array($data[0])) {
            throw new ReadException($this->path());
        }

        return $data[0];
    }

    /**
     * @param string $line
     *
     * @return array
     */
    protected function parseColumnNamesHeader($line)
    {
        $data = $this->parseArrayYaml($line);

        return $data['columns'];
    }

    /**
     * @param string $yaml
     *
     * @return mixed
     */
    protected function parseYaml($yaml)
    {
        try {
            $data = $this->parser()->parse($yaml);
        } catch (ParseException $e) {
            throw new ReadException($this->path(), $e);
        }

        return $data;
    }

    /**
     * @param string $yaml
     *
     * @return array
     */
    protected function parseArrayYaml($yaml)
    {
        $data = $this->parseYaml($yaml);
        if (!is_array($data)) {
            throw new ReadException($this->path());
        }

        return $data;
    }

    /**
     * @return string|null
     */
    protected function readLine()
    {
        try {
            $this->currentLine = $this->isolator()->fgets($this->stream());
        } catch (ErrorException $e) {
            throw new ReadException($this->path(), $e);
        }

        if (false === $this->currentLine) {
            return null;
        }

        return $this->currentLine;
    }

    /**
     * @return string|null
     */
    protected function readNonEmptyLine()
    {
        do {
            $line = $this->readLine();
            if (null === $line) {
                break;
            }

            $trimmedLine = trim($line);
        } while (
            '' === $trimmedLine ||
            '#' === $trimmedLine[0]
        );

        return $line;
    }

    /**
     * @return string|null
     */
    protected function readExpandedRowLines()
    {
        if (null === $this->currentLine) {
            return null;
        }

        $lines = array();
        do {
            $lines[] = $this->currentLine;
            $this->currentLine = $this->readLine();
        } while (
            null !== $this->currentLine &&
            '- ' !== substr($this->currentLine, 0, 2)
        );

        return implode('', $lines);
    }

    /**
     * @return integer
     */
    protected function streamPosition()
    {
        try {
            $position = $this->isolator()->ftell($this->stream());
        } catch (ErrorException $e) {
            throw new ReadException($this->path(), $e);
        }

        return $position;
    }

    private $parser;

    private $current;
    private $index;
    private $rewindOffset;
    private $currentLine;
    private $isExhausted;

    private $isExpanded;
    private $columnNames;
    private $expectedRowKeys;
}
