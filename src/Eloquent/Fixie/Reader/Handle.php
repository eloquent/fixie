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

use ErrorException;
use Icecave\Isolator\Isolator;
use Iterator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class Handle implements Iterator
{
    /**
     * @param stream {readable: true} $stream
     * @param string                  $path
     * @param Parser|null             $parser
     * @param Isolator|null           $isolator
     */
    public function __construct(
        $stream,
        $path = null,
        Parser $parser = null,
        Isolator $isolator = null
    ) {
        if (null === $parser) {
            $parser = new Parser;
        }

        $this->stream = $stream;
        $this->path = $path;
        $this->parser = $parser;
        $this->isolator = Isolator::get($isolator);
        $this->rewindOffset = 0;
        $this->isExhausted = false;
    }

    /**
     * @return stream {readable: true}
     */
    public function stream()
    {
        return $this->stream;
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->path;
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
            $this->isolator->fseek($this->stream(), $this->rewindOffset);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($this->path(), $e);
        }

        $this->current = null;
        $this->index = null;
        $this->isExhausted = false;
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

        if ('columns: [' === substr($line, 0, 10)) {
            $this->columnNames = $this->parseColumnNamesHeader($line);
            $this->expectedRowKeys = range(0, count($this->columnNames) - 1);

            $line = $this->readNonEmptyLine();
            if ("data: [\n" !== $line) {
                throw new Exception\ReadException($this->path());
            }
            $this->rewindOffset = $this->streamPosition();

            $row = $this->parseSubsequentRow();
        } elseif ('data: [' === trim($line)) {
            $this->rewindOffset = $this->streamPosition();
            $line = $this->readNonEmptyLine();
            if (null === $line) {
                throw new Exception\ReadException($this->path());
            }

            if (']' === trim($line)) {
                $row = null;
                $this->columnNames = array();
                $this->expectedRowKeys = array();
            } else {
                $row = $this->parseRowYaml($line);
                $this->columnNames = array_keys($row);
                $this->expectedRowKeys = range(0, count($this->columnNames) - 1);
            }
        } else {
            throw new Exception\ReadException($this->path());
        }

        return $row;
    }

    /**
     * @return array|null
     */
    protected function parseSubsequentRow()
    {
        $line = $this->readNonEmptyLine();
        if (null === $line) {
            throw new Exception\ReadException($this->path());
        }
        if (']' === trim($line)) {
            return null;
        }

        $row = $this->parseRowYaml($line);
        if (array_keys($row) !== $this->expectedRowKeys) {
            throw new Exception\ReadException($this->path());
        }

        return array_combine($this->columnNames, $row);
    }

    /**
     * @param string $yaml
     *
     * @return array
     */
    protected function parseRowYaml($yaml)
    {
        if (",\n" !== substr($yaml, -2)) {
            throw new Exception\ReadException($this->path());
        }

        return $this->parseArrayYaml(substr($yaml, 0, -2));
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
            throw new Exception\ReadException($this->path(), $e);
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
            throw new Exception\ReadException($this->path());
        }

        return $data;
    }

    /**
     * @return string|null
     */
    protected function readLine()
    {
        try {
            $line = $this->isolator->fgets($this->stream());
        } catch (ErrorException $e) {
            throw new Exception\ReadException($this->path(), $e);
        }

        if (false === $line) {
            return null;
        }

        return $line;
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
     * @return integer
     */
    protected function streamPosition()
    {
        try {
            $position = $this->isolator->ftell($this->stream());
        } catch (ErrorException $e) {
            throw new Exception\ReadException($this->path(), $e);
        }

        return $position;
    }

    private $stream;
    private $path;
    private $parser;
    private $isolator;

    private $current;
    private $index;
    private $rewindOffset;
    private $isExhausted;

    private $columnNames;
    private $expectedRowKeys;
}
