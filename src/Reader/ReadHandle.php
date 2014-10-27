<?php

/*
 * This file is part of the Fixie package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Fixie\Reader;

use Eloquent\Fixie\Handle\AbstractHandle;
use Eloquent\Fixie\Handle\Exception\IoExceptionInterface;
use Eloquent\Fixie\Handle\Exception\ReadException;
use ErrorException;
use Icecave\Isolator\Isolator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * A readable data handle.
 */
class ReadHandle extends AbstractHandle implements ReadHandleInterface
{
    /**
     * Construct a new readable data handle.
     *
     * @param stream|null   $stream   The native stream handle, or null to create lazily from the filesystem path.
     * @param string|null   $path     The filesystem path, or null if the path is unknown.
     * @param Parser|null   $parser   The YAML parser to use.
     * @param Isolator|null $isolator The isolator to use.
     */
    public function __construct(
        $stream = null,
        $path = null,
        Parser $parser = null,
        Isolator $isolator = null
    ) {
        parent::__construct($stream, $path, $isolator);

        if (null === $parser) {
            $parser = new Parser();
        }

        $this->parser = $parser;
        $this->rewindOffset = 0;
        $this->isExhausted = false;
        $this->isExpanded = false;
        $this->sequence = -1;
    }

    /**
     * Get the YAML parser.
     *
     * @return Parser The YAML parser.
     */
    public function parser()
    {
        return $this->parser;
    }

    /**
     * Rewind this handle to the first data row.
     *
     * @throws ReadException If rewinding fails.
     */
    public function rewind()
    {
        try {
            $this->isolator()->fseek($this->stream(), $this->rewindOffset);
        } catch (ErrorException $e) {
            throw new ReadException($this->path(), $e);
        }

        $this->current = null;
        $this->key = null;
        $this->isExhausted = false;
        $this->currentLine = null;
        $this->sequence = -1;

        if ($this->isExpanded) {
            $this->readLine();
        }
    }

    /**
     * Returns true if the current row key is valid.
     *
     * @return boolean       True if the position is valid.
     * @throws ReadException If data is unable to be read.
     */
    public function valid()
    {
        $this->populateCurrent();

        return null !== $this->key;
    }

    /**
     * Get the current data row.
     *
     * @return array|null    The current data row, or null if there is no current row.
     * @throws ReadException If data is unable to be read.
     */
    public function current()
    {
        $this->populateCurrent();

        return $this->current;
    }

    /**
     * Get the current row key.
     *
     * @return integer|string|null The current row key, or null if there is no current row.
     * @throws ReadException       If data is unable to be read.
     */
    public function key()
    {
        $this->populateCurrent();

        return $this->key;
    }

    /**
     * Advance to the next data row.
     */
    public function next()
    {
        $this->current = null;
    }

    /**
     * Read and return a single data row.
     *
     * @return tuple<mixed,array>|null The data key and row, or null if the end of data was encountered.
     * @throws ReadException           If data is unable to be read.
     */
    public function read()
    {
        $this->populateCurrent();
        $key = $this->key;
        $current = $this->current;
        $this->next();

        if (null === $current) {
            return null;
        }

        return array($key, $current);
    }

    /**
     * Read and return all data rows.
     *
     * @return array<array>  All data rows.
     * @throws ReadException If data is unable to be read.
     */
    public function readAll()
    {
        return iterator_to_array($this);
    }

    /**
     * Open a stream to the file at the pre-defined path.
     *
     * @return stream               The stream.
     * @throws IoExceptionInterface If opening the stream fails.
     */
    protected function openStream()
    {
        try {
            $stream = $this->isolator()->fopen($this->path(), 'rb');
        } catch (ErrorException $e) {
            throw new ReadException($this->path(), $e);
        }

        return $stream;
    }

    /**
     * Read and parse the next data row, storing the results as the 'current'
     * row and row key.
     *
     * @throws ReadException If data is unable to be read.
     */
    protected function populateCurrent()
    {
        if ($this->isExhausted || null !== $this->current) {
            return;
        }

        list($this->key, $this->current) = $this->parseRow();

        if (null === $this->current) {
            $this->isExhausted = true;
            $this->key = null;
        } elseif (null === $this->key) {
            $this->key = ++$this->sequence;
        }
    }

    /**
     * Parse and return the next data row.
     *
     * @return tuple<mixed,array|null> The data key and row, or null if the end of data was encountered.
     * @throws ReadException           If data is unable to be read.
     */
    protected function parseRow()
    {
        if (null === $this->columnNames) {
            return $this->parseFirstRow();
        }

        return $this->parseSubsequentRow();
    }

    /**
     * Parse and return the first data row.
     *
     * @return tuple<mixed,array|null> The data key and row, or null if the end of data was encountered.
     * @throws ReadException           If data is unable to be read.
     */
    protected function parseFirstRow()
    {
        $line = $this->readNonEmptyLine();
        if (null === $line) {
            return $line;
        }

        $this->rewindOffset = $this->streamPosition() - strlen($line);

        if ('columns:' === substr($line, 0, 8)) {
            $this->isExpanded = false;

            do {
                $lines[] = $line;
                $line = $this->readNonEmptyLine();
            } while (
                null !== $line &&
                "data: [\n" !== $line &&
                "data: {\n" !== $line
            );

            $this->columnNames = $this->parseColumnNamesHeader(implode($lines));
            $this->expectedRowKeys = range(0, count($this->columnNames) - 1);
            $this->rewindOffset = $this->streamPosition();

            $row = $this->parseSubsequentRow();
        } elseif ("-\n" === $line || '- ' === substr($line, 0, 2)) {
            $this->isExpanded = true;
            $row = $this->parseExpandedRowYaml($this->readExpandedRowLines());
            $this->columnNames = $this->expectedRowKeys = array_keys($row[1]);
        } else {
            $trimmedLine = trim($line);

            if ('data: [' === $trimmedLine || 'data: {' === $trimmedLine) {
                $this->isExpanded = false;
                $this->rewindOffset = $this->streamPosition();
                $line = $this->readNonEmptyLine();
                if (null === $line) {
                    throw new ReadException($this->path());
                }

                $trimmedLine = trim($line);

                if (']' === $trimmedLine || '}' === $trimmedLine) {
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
        }

        return $row;
    }

    /**
     * Parse and return a subsequent data row.
     *
     * @return tuple<mixed,array|null> The data key and row, or null if the end of data was encountered.
     * @throws ReadException           If data is unable to be read.
     */
    protected function parseSubsequentRow()
    {
        if ($this->isExpanded) {
            return $this->parseSubsequentExpandedRow();
        }

        return $this->parseSubsequentCompactRow();
    }

    /**
     * Parse and return a subsequent data row in 'compact' form.
     *
     * @return tuple<mixed,array|null> The data key and row, or null if the end of data was encountered.
     * @throws ReadException           If data is unable to be read.
     */
    protected function parseSubsequentCompactRow()
    {
        $line = $this->readNonEmptyLine();
        if (null === $line) {
            throw new ReadException($this->path());
        }

        $trimmedLine = trim($line);

        if (']' === $trimmedLine || '}' === $trimmedLine) {
            return array(null, null);
        }

        list($key, $row) = $this->parseCompactRowYaml($line);
        if (array_keys($row) !== $this->expectedRowKeys) {
            throw new ReadException($this->path());
        }

        return array($key, array_combine($this->columnNames, $row));
    }

    /**
     * Parse and return a subsequent data row in 'expanded' form.
     *
     * @return tuple<mixed,array|null> The data key and row, or null if the end of data was encountered.
     * @throws ReadException           If data is unable to be read.
     */
    protected function parseSubsequentExpandedRow()
    {
        $data = $this->readExpandedRowLines();
        if (null === $data) {
            return array(null, null);
        }

        list($key, $row) = $this->parseExpandedRowYaml($data);
        if (array_keys($row) !== $this->expectedRowKeys) {
            throw new ReadException($this->path());
        }

        return array($key, $row);
    }

    /**
     * Parse a 'compact' form data row from the supplied YAML data.
     *
     * @param string $yaml The YAML data to parse.
     *
     * @return array         The parsed data row.
     * @throws ReadException If data is unable to be parsed.
     */
    protected function parseCompactRowYaml($yaml)
    {
        if (",\n" !== substr($yaml, -2)) {
            throw new ReadException($this->path());
        }

        $data = $this->parseArrayYaml(substr($yaml, 0, -2));

        if (1 === count($data)) {
            foreach ($data as $key => $subData) {}

            if (is_string($key) && is_array($subData)) {
                return array($key, $subData);
            }
        }

        return array(null, $data);
    }

    /**
     * Parse an 'expanded' form data row from the supplied YAML data.
     *
     * @param string $yaml The YAML data to parse.
     *
     * @return array         The parsed data row.
     * @throws ReadException If data is unable to be parsed.
     */
    protected function parseExpandedRowYaml($yaml)
    {
        $data = $this->parseArrayYaml($yaml);

        if (!is_array($data[0])) {
            throw new ReadException($this->path());
        }

        if (1 === count($data[0])) {
            foreach ($data[0] as $key => $subData) {}

            if (is_string($key) && is_array($subData)) {
                return array($key, $subData);
            }
        }

        return array(null, $data[0]);
    }

    /**
     * Parse a 'compact' form column names row from the supplied YAML data.
     *
     * @param string $yaml The YAML data to parse.
     *
     * @return array         The parsed column names.
     * @throws ReadException If data is unable to be parsed.
     */
    protected function parseColumnNamesHeader($yaml)
    {
        $data = $this->parseArrayYaml($yaml);
        if (
            !array_key_exists('columns', $data) ||
            !is_array($data['columns'])
        ) {
            throw new ReadException($this->path());
        }

        return $data['columns'];
    }

    /**
     * Parse the supplied YAML data.
     *
     * @param string $yaml The YAML data to parse.
     *
     * @return mixed         The parsed value.
     * @throws ReadException If data is unable to be parsed.
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
     * Parse the supplied YAML data and assert that the parsed value is an
     * array.
     *
     * @param string $yaml The YAML data to parse.
     *
     * @return array         The parsed array.
     * @throws ReadException If data is unable to be parsed.
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
     * Read the next line from the native stream handle.
     *
     * @return string|null   The line, or null if the end of data was encountered.
     * @throws ReadException If data is unable to be read.
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
     * Read the next non-empty, non-comment line from the native stream handle.
     *
     * @return string|null   The line, or null if the end of data was encountered.
     * @throws ReadException If data is unable to be read.
     */
    protected function readNonEmptyLine()
    {
        do {
            $line = $this->readLine();
            if (null === $line) {
                break;
            }

            $trimmedLine = trim($line);
        } while ('' === $trimmedLine || '#' === $trimmedLine[0]);

        return $line;
    }

    /**
     * Read all lines until the end of an 'expanded' style block from the native
     * stream handle.
     *
     * @return string|null   The lines, or null if the end of data was encountered.
     * @throws ReadException If data is unable to be read.
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
            "-\n" !== $this->currentLine &&
            '- ' !== substr($this->currentLine, 0, 2)
        );

        return implode('', $lines);
    }

    /**
     * Get the current position of the native stream handle.
     *
     * @return integer       The current position.
     * @throws ReadException If the position could not be determined.
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
    private $key;
    private $rewindOffset;
    private $currentLine;
    private $isExhausted;

    private $isExpanded;
    private $columnNames;
    private $expectedRowKeys;
    private $sequence;
}
