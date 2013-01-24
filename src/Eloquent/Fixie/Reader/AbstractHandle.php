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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

abstract class AbstractHandle implements HandleInterface
{
    /**
     * @param stream {readable: true} $stream
     * @param string|null             $path
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
        $this->position = 0;
        $this->startLine = 0;
        $this->isolator = Isolator::get($isolator);

        $this->parseHeader();
    }

    /**
     * @return stream {readable: true}
     */
    public function stream()
    {
        return $this->stream;
    }

    /**
     * @return string|null
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

    /**
     * @return integer|null
     */
    public function position()
    {
        return $this->position;
    }

    /**
     * @return array<array>
     */
    public function fetchAll()
    {
        $this->rewindHandle();

        $rows = array();
        while (null !== ($row = $this->fetch())) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array|null
     */
    public function fetch()
    {
        $this->currentRow = $this->fetchRow(true);
        if (null === $this->currentRow) {
            $this->position = null;
        } else {
            $this->position ++;
        }

        return $this->currentRow;
    }

    public function rewindHandle()
    {
        $this->rewindStream();
        for ($i = 0; $i < $this->startLine; $i ++) {
            $this->readLine();
        }

        $this->position = 0;
    }

    public function rewind()
    {
        $this->rewindHandle();
        $this->fetch();
    }

    /**
     * @return array|null
     */
    public function current()
    {
        return $this->currentRow;
    }

    /**
     * @return integer|null
     */
    public function key()
    {
        return $this->position() - 1;
    }

    public function next()
    {
        $this->fetch();
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        return null !== $this->position();
    }

    protected function rewindStream()
    {
        try {
            $this->isolator->rewind($this->stream());
        } catch (ErrorException $e) {
            throw new Exception\ReadException($this->path(), $e);
        }
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
        } while (
            null !== $line &&
            '' === trim($line)
        );

        return $line;
    }

    /**
     * @param string $line
     * @param string $string
     *
     * @return boolean
     */
    protected function lineEquals($line, $string)
    {
        if ("\n" === $line[strlen($line) - 1]) {
            $line = substr($line, 0, -1);
        }

        return $string === $line;
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
    protected function parseYamlArray($yaml)
    {
        $data = $this->parseYaml($yaml);
        if (!is_array($data)) {
            throw new Exception\ReadException($this->path());
        }

        return $data;
    }

    abstract protected function parseHeader();

    /**
     * @return array|null
     */
    abstract protected function fetchRow();

    private $stream;
    private $path;
    private $parser;
    private $currentRow;
    protected $position;
    protected $isolator;
    protected $columnNames;
    protected $startLine;
}
